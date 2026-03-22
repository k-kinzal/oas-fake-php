<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

use function is_string;

use LogicException;
use OasFake\Exception\ReplayMismatchError;
use OasFake\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function strtolower;

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

    private Converter $converter;

    private ?Cassette $cassette = null;

    /** @var array<string, int> */
    private array $indexTable = [];

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private string $mode,
        private string $cassettePath,
        private Schema $schema,
        private Validator $validator,
        private array $fakerOptions,
        private HandlerMap $handlers,
        private bool $validateRequests,
        private bool $validateResponses,
        private array $middleware = [],
    ) {
        $this->converter = new Converter();
    }

    /**
     * Activate the interceptor and initialize cassette for RECORD/REPLAY modes.
     */
    public function start(): void
    {
        if ($this->running) {
            return;
        }

        if ($this->mode === Mode::RECORD || $this->mode === Mode::REPLAY) {
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

        $operation = null;
        if ($this->validateRequests) {
            $operation = $this->validator->validateRequest($psrRequest);
        } else {
            try {
                $operation = $this->validator->validateRequest($psrRequest);
            } catch (ValidationException) {
                // Validation disabled: silently ignore invalid requests
            }
        }

        $operationId = $this->findOperationId($psrRequest);
        $path = $psrRequest->getUri()->getPath();
        $method = $psrRequest->getMethod();

        $handler = $this->handlers->find($operationId ?? '', $path, $method);

        $statusCode = $this->resolveStatusCode($psrRequest);

        $matchedPath = $this->findMatchingPath($psrRequest);

        if ($handler !== null) {
            $fakerDefault = null;
            if ($operation !== null && $this->hasResponseBody($psrRequest) && $matchedPath !== null) {
                $fakerDefault = FakeResponse::generateResponse($this->schema, $matchedPath, $method, $statusCode, $this->fakerOptions);
            }
            $response = $handler->resolve($psrRequest, $fakerDefault);
        } elseif ($operation !== null) {
            if ($this->hasResponseBody($psrRequest) && $matchedPath !== null) {
                $response = FakeResponse::generateResponse($this->schema, $matchedPath, $method, $statusCode, $this->fakerOptions);
            } else {
                $response = new \GuzzleHttp\Psr7\Response($statusCode);
            }
        } else {
            $response = new \GuzzleHttp\Psr7\Response(500, ['Content-Type' => 'application/json'], (string) json_encode([
                'error' => 'Could not resolve operation from request',
            ]));
        }

        $response = $this->runMiddleware($psrRequest, $response);

        if ($this->validateResponses && $operation !== null) {
            $this->validator->validateResponse($operation, $response);
        }

        $vcrResponse = $this->converter->psr7ToVcrResponse($response);

        if ($this->mode === Mode::RECORD) {
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

    private function findMatchingPath(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());
        $openApi = $this->schema->openApi();

        if ($openApi->paths === null) {
            return null;
        }

        /** @var PathItem $pathItem */
        foreach ($openApi->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            if ($this->matchPath($pathPattern, $path)) {
                $operation = $this->getOperationFromPathItem($pathItem, $method);
                if ($operation !== null) {
                    return $pathPattern;
                }
            }
        }

        return null;
    }

    private function findOperationId(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());
        $openApi = $this->schema->openApi();

        if ($openApi->paths === null) {
            return null;
        }

        /** @var PathItem $pathItem */
        foreach ($openApi->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            if ($this->matchPath($pathPattern, $path)) {
                $operation = $this->getOperationFromPathItem($pathItem, $method);
                if ($operation !== null && $operation->operationId !== null) {
                    return $operation->operationId;
                }
            }
        }

        return null;
    }

    private function hasResponseBody(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());
        $openApi = $this->schema->openApi();

        if ($openApi->paths !== null) {
            foreach ($openApi->paths as $pathPattern => $pathItem) {
                if (!is_string($pathPattern)) {
                    continue;
                }

                if ($this->matchPath($pathPattern, $path)) {
                    $operation = $this->getOperationFromPathItem($pathItem, $method);
                    if ($operation !== null && $operation->responses !== null) {
                        foreach ($operation->responses as $code => $response) {
                            if (!is_int($code) && !is_string($code)) {
                                continue;
                            }
                            $numericCode = (int) $code;
                            if ($numericCode >= 200 && $numericCode < 300) {
                                return $response->content !== null && $response->content !== [];
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function resolveStatusCode(ServerRequestInterface $request): int
    {
        $path = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());
        $openApi = $this->schema->openApi();

        if ($openApi->paths !== null) {
            foreach ($openApi->paths as $pathPattern => $pathItem) {
                if (!is_string($pathPattern)) {
                    continue;
                }

                if ($this->matchPath($pathPattern, $path)) {
                    $operation = $this->getOperationFromPathItem($pathItem, $method);
                    if ($operation !== null && $operation->responses !== null) {
                        foreach ($operation->responses as $code => $response) {
                            if (!is_int($code) && !is_string($code)) {
                                continue;
                            }
                            $numericCode = (int) $code;
                            if ($numericCode >= 200 && $numericCode < 300) {
                                return $numericCode;
                            }
                        }
                    }
                }
            }
        }

        return 200;
    }

    private function getOperationFromPathItem(PathItem $pathItem, string $method): ?Operation
    {
        return match ($method) {
            'get' => $pathItem->get,
            'post' => $pathItem->post,
            'put' => $pathItem->put,
            'delete' => $pathItem->delete,
            'patch' => $pathItem->patch,
            'options' => $pathItem->options,
            'head' => $pathItem->head,
            'trace' => $pathItem->trace,
            default => null,
        };
    }

    private function matchPath(string $pattern, string $path): bool
    {
        $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $pattern);
        if ($regex === null) {
            return false;
        }

        $regex = '#^' . $regex . '$#';

        return preg_match($regex, $path) === 1;
    }

    private function runMiddleware(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->middleware === []) {
            return $response;
        }

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $chain = $handler;
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $chain;
            $chain = new class ($middleware, $next) implements RequestHandlerInterface {
                public function __construct(
                    private MiddlewareInterface $middleware,
                    private RequestHandlerInterface $next,
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $chain->handle($request);
    }
}
