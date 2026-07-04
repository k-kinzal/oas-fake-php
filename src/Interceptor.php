<?php

declare(strict_types=1);

namespace OasFake;

use League\OpenAPIValidation\PSR7\OperationAddress;
use LogicException;
use OasFake\Exception\ReplayMismatchError;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use VCR\Cassette;
use VCR\Configuration;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\Storage\Json;

/**
 * Request handler with OpenAPI validation, fake response generation, and cassette management.
 *
 * Handles the full request lifecycle: conversion, validation, stub/faker resolution,
 * middleware execution, and response conversion. Manages cassettes for RECORD/REPLAY modes.
 *
 * VCR lifecycle (turnOn/turnOff, hook registration) is managed externally by Server or ServerRegistry.
 */
final class Interceptor
{
    private bool $running = false;

    private Mode $mode;

    private Converter $converter;

    private ?Cassette $cassette = null;

    private OperationLookup $operationLookup;

    private OperationPathResolver $operationPathResolver;

    private OperationResponder $operationResponder;

    private MiddlewarePipeline $middlewarePipeline;

    /** @var array<string, int> */
    private array $indexTable = [];

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        string|Mode $mode,
        private string $cassettePath,
        private Schema $schema,
        private Validator $validator,
        array $fakerOptions,
        HandlerMap $handlers,
        private bool $validateRequests,
        private bool $validateResponses,
        array $middleware = [],
    ) {
        $this->mode = Mode::from($mode);
        $this->converter = new Converter();
        $fakeDataContext = new FakeDataContext($schema, $fakerOptions);
        $this->operationLookup = $fakeDataContext->operationLookup();
        $this->operationPathResolver = new OperationPathResolver();
        $this->operationResponder = new OperationResponder($fakeDataContext, $handlers);
        $this->middlewarePipeline = new MiddlewarePipeline($middleware);
    }

    /**
     * Activate the interceptor and initialize cassette for RECORD/REPLAY modes.
     */
    public function start(): void
    {
        if ($this->running) {
            return;
        }

        if ($this->mode->isRecord() || $this->mode->isReplay()) {
            $this->initCassette();
        }

        $this->running = true;
    }

    /**
     * Deactivate the interceptor and release the cassette.
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->cassette = null;
        $this->indexTable = [];
        $this->running = false;
    }

    /**
     * Check whether the interceptor is currently active.
     *
     * @return bool True if the interceptor has been started and not yet stopped
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Handle an intercepted HTTP request and return a fake or validated response.
     *
     * Converts the VCR request to PSR-7, validates against the OpenAPI schema,
     * resolves a stub or generates a fake response, runs middleware, and converts back.
     * In RECORD mode, the request/response pair is also written to the cassette.
     *
     * @param VcrRequest $vcrRequest The intercepted HTTP request from PHP-VCR
     *
     * @return VcrResponse The generated or stubbed response in VCR format
     */
    public function handle(VcrRequest $vcrRequest): VcrResponse
    {
        $psrRequest = $this->converter->requestToPsr7($vcrRequest);
        $path = $this->operationPathResolver->resolve($this->schema, $psrRequest);
        $method = $psrRequest->getMethod();
        $operationInfo = $this->operationLookup->findByRequestPathAndMethod($path, $method);
        $operation = $this->resolveOperation($psrRequest, $operationInfo);
        $response = $this->operationResponder->respond($psrRequest, $path, $method, $operationInfo);
        $response = $this->middlewarePipeline->process($psrRequest, $response);

        if ($this->validateResponses && $operation !== null) {
            $this->validator->validateResponse($operation, $response);
        }

        $vcrResponse = $this->converter->psr7ToVcrResponse($response);

        if ($this->mode->isRecord()) {
            $this->recordToCassette($vcrRequest, $vcrResponse);
        }

        return $vcrResponse;
    }

    /**
     * Replay a previously recorded response from the cassette.
     *
     * Looks up the request in the cassette and returns the matching response.
     * Throws ReplayMismatchError if no matching recording is found.
     *
     * @param VcrRequest $request The intercepted HTTP request
     *
     * @throws ReplayMismatchError If no matching cassette recording exists
     *
     * @return VcrResponse The recorded response from the cassette
     */
    public function replay(VcrRequest $request): VcrResponse
    {
        $psrRequest = $this->converter->requestToPsr7($request);
        $path = $this->operationPathResolver->resolve($this->schema, $psrRequest);
        $operationInfo = $this->operationLookup->findByRequestPathAndMethod($path, $psrRequest->getMethod());
        $operation = $this->resolveOperation($psrRequest, $operationInfo);
        $response = $this->converter->vcrResponseToPsr7($this->playback($request));
        $response = $this->middlewarePipeline->process($psrRequest, $response);

        if ($this->validateResponses && $operation !== null) {
            $this->validator->validateResponse($operation, $response);
        }

        return $this->converter->psr7ToVcrResponse($response);
    }

    private function playback(VcrRequest $request): VcrResponse
    {
        if ($this->cassette === null) {
            throw ReplayMismatchError::forRequest(
                $request,
                new LogicException('No cassette loaded for replay'),
            );
        }

        $index = $this->nextIndex($request);
        $response = $this->cassette->playback($request, $index);

        if ($response === null) {
            throw ReplayMismatchError::forRequest(
                $request,
                new LogicException('No matching cassette recording'),
            );
        }

        return $response;
    }

    private function resolveOperation(ServerRequestInterface $request, ?OperationInfo $operationInfo): ?OperationAddress
    {
        if ($this->validateRequests) {
            return $this->validator->validateRequest($request);
        }

        if ($operationInfo === null) {
            return null;
        }

        return new OperationAddress($operationInfo->pathPattern, $operationInfo->method);
    }

    private function initCassette(): void
    {
        $storage = new Json($this->cassettePath, 'recording');
        $config = new Configuration();
        $this->cassette = new Cassette('recording', $config, $storage);
    }

    private function recordToCassette(VcrRequest $request, VcrResponse $response): void
    {
        if ($this->cassette === null) {
            return;
        }

        $this->cassette->record($request, $response, $this->nextIndex($request));
    }

    private function nextIndex(VcrRequest $request): int
    {
        $key = $request->getMethod() . ' ' . ($request->getUrl() ?? '');
        if (!isset($this->indexTable[$key])) {
            $this->indexTable[$key] = -1;
        }

        return ++$this->indexTable[$key];
    }
}
