<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\ReplayMismatchError;
use Psr\Http\Server\MiddlewareInterface;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

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

    private MiddlewarePipeline $middlewarePipeline;

    private CassetteSession $cassetteSession;

    private OperationRequestResolver $operationResolver;

    private SchemaRequestHandler $requestHandler;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        string|Mode $mode,
        string $cassettePath,
        Schema $schema,
        Validator $validator,
        array $fakerOptions,
        HandlerMap $handlers,
        bool $validateRequests,
        bool $validateResponses,
        array $middleware = [],
        string $cassetteName = 'recording',
    ) {
        $this->mode = Mode::from($mode);
        $this->converter = new Converter();
        $fakeDataContext = new FakeDataContext($schema, $fakerOptions);
        $this->operationResolver = new OperationRequestResolver(
            $schema,
            $fakeDataContext->operationLookup(),
            new OperationPathResolver(),
            $validator,
            $validateRequests,
        );
        $this->requestHandler = new SchemaRequestHandler(
            $this->operationResolver,
            new OperationResponder($fakeDataContext, $handlers),
            $validator,
            $validateResponses,
        );
        $this->middlewarePipeline = new MiddlewarePipeline($middleware);
        $this->cassetteSession = new CassetteSession($cassettePath, $cassetteName);
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
            $this->cassetteSession->start();
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

        $this->cassetteSession->stop();
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
        $response = $this->middlewarePipeline->handle(
            $psrRequest,
            $this->requestHandler,
        );

        $vcrResponse = $this->converter->psr7ToVcrResponse($response);

        if ($this->mode->isRecord()) {
            $this->cassetteSession->record($vcrRequest, $vcrResponse);
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
        $operationRequest = $this->operationResolver->resolve($psrRequest);
        $response = $this->converter->vcrResponseToPsr7($this->cassetteSession->playback($request));
        $response = $this->middlewarePipeline->process($psrRequest, $response);
        $this->requestHandler->validateResponse($operationRequest, $response);

        return $this->converter->psr7ToVcrResponse($response);
    }
}
