<?php

declare(strict_types=1);

namespace OasFake;

use LogicException;
use OasFake\Exception\ReplayMismatchError;
use OasFake\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    private OperationLookup $operationLookup;

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
        $this->operationLookup = new OperationLookup($schema);
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
            }
        }

        $path = $this->operationPathForRequest($psrRequest);
        $method = $psrRequest->getMethod();
        $operationInfo = $this->operationLookup->findByRequestPathAndMethod($path, $method);
        $operationId = $operationInfo?->operationId;

        $handler = $this->handlers->find($operationId ?? '', $path, $method, $operationInfo?->pathPattern);

        $statusCode = $this->resolveStatusCode($operationInfo);

        if ($handler !== null) {
            $fakerDefault = null;
            if ($operationInfo !== null && $this->hasResponseBody($operationInfo)) {
                $fakerDefault = FakeResponse::generateResponse($this->schema, $operationInfo->pathPattern, $method, $statusCode, $this->fakerOptions);
            }
            $response = $handler->resolve($psrRequest, $fakerDefault);
        } elseif ($operationInfo !== null) {
            if ($this->hasResponseBody($operationInfo)) {
                $response = FakeResponse::generateResponse($this->schema, $operationInfo->pathPattern, $method, $statusCode, $this->fakerOptions);
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

    private function hasResponseBody(OperationInfo $operationInfo): bool
    {
        if ($operationInfo->operation->responses !== null) {
            foreach ($operationInfo->operation->responses as $code => $response) {
                if (!is_int($code) && !is_string($code)) {
                    continue;
                }
                $numericCode = (int) $code;
                if ($numericCode >= 200 && $numericCode < 300) {
                    return $response->content !== null && $response->content !== [];
                }
            }
        }

        return true;
    }

    private function resolveStatusCode(?OperationInfo $operationInfo): int
    {
        if ($operationInfo?->operation->responses !== null) {
            foreach ($operationInfo->operation->responses as $code => $response) {
                if (!is_int($code) && !is_string($code)) {
                    continue;
                }
                $numericCode = (int) $code;
                if ($numericCode >= 200 && $numericCode < 300) {
                    return $numericCode;
                }
            }
        }

        return 200;
    }

    private function operationPathForRequest(ServerRequestInterface $request): string
    {
        $path = $this->normalizePath($request->getUri()->getPath());

        foreach ($this->schema->serverUrls() as $serverUrl) {
            $base = parse_url($serverUrl);
            if (!is_array($base) || !$this->serverUrlMatchesRequest($request, $base)) {
                continue;
            }

            $basePath = $this->normalizePath((string) ($base['path'] ?? '/'));
            if ($basePath === '/') {
                return $path;
            }

            if ($path === $basePath) {
                return '/';
            }

            if (str_starts_with($path, $basePath . '/')) {
                $operationPath = substr($path, strlen($basePath));

                return $operationPath === '' ? '/' : $operationPath;
            }
        }

        return $path;
    }

    /**
     * @param array{scheme?: string, host?: string, port?: int|string, path?: string} $base
     */
    private function serverUrlMatchesRequest(ServerRequestInterface $request, array $base): bool
    {
        $uri = $request->getUri();

        if (isset($base['scheme']) && strtolower($uri->getScheme()) !== strtolower((string) $base['scheme'])) {
            return false;
        }

        if (isset($base['host']) && strtolower($uri->getHost()) !== strtolower((string) $base['host'])) {
            return false;
        }

        $basePort = $this->effectivePort($base);
        if ($basePort !== null && $this->effectiveRequestPort($request) !== $basePort) {
            return false;
        }

        return true;
    }

    /**
     * @param array{scheme?: string, port?: int|string} $url
     */
    private function effectivePort(array $url): ?int
    {
        if (isset($url['port'])) {
            return (int) $url['port'];
        }

        return match (strtolower((string) ($url['scheme'] ?? ''))) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    private function effectiveRequestPort(ServerRequestInterface $request): ?int
    {
        $uri = $request->getUri();
        if ($uri->getPort() !== null) {
            return $uri->getPort();
        }

        return match (strtolower($uri->getScheme())) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        $normalized = rtrim($normalized, '/');

        return $normalized === '' ? '/' : $normalized;
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
