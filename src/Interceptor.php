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

use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;
use VCR\VCRFactory;
use VCR\Videorecorder;

/**
 * HTTP interceptor that bridges PHP-VCR with OpenAPI validation and fake response generation.
 *
 * Handles the full request lifecycle: conversion, validation, stub/faker resolution,
 * middleware execution, and response conversion.
 */
final class Interceptor
{
    private bool $running = false;

    private Converter $converter;

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
        private bool $managed = false,
    ) {
        $this->converter = new Converter();
    }

    /**
     * Activate the interceptor and configure PHP-VCR hooks.
     *
     * In managed mode, only marks the interceptor as running without touching VCR.
     */
    public function start(): void
    {
        if ($this->running) {
            return;
        }

        if ($this->managed) {
            $this->running = true;

            return;
        }

        $this->configureVcr();
        VCR::turnOn();
        $this->postStartSetup();

        if ($this->mode === Mode::FAKE) {
            $this->registerFakeHooks();
        } elseif ($this->mode === Mode::REPLAY) {
            $this->registerReplayHooks();
        }

        $this->running = true;
    }

    /**
     * Deactivate the interceptor and turn off PHP-VCR.
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        if (!$this->managed) {
            VCR::turnOff();
        }

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

        return $this->converter->psr7ToVcrResponse($response);
    }

    private function configureVcr(): void
    {
        VCR::configure()
            ->setCassettePath($this->cassettePath)
            ->setStorage('json')
            ->enableLibraryHooks(['curl', 'stream_wrapper']);

        match ($this->mode) {
            Mode::FAKE => $this->configureFakeMode(),
            Mode::RECORD => $this->configureRecordMode(),
            Mode::REPLAY => $this->configureReplayMode(),
            default => $this->configureFakeMode(),
        };
    }

    private function configureFakeMode(): void
    {
        VCR::configure()
            ->setMode('none')
            ->addRequestMatcher(
                'fake_matcher',
                static function (): bool {
                    return true;
                },
            );
    }

    private function configureRecordMode(): void
    {
        VCR::configure()->setMode('new_episodes');
    }

    private function configureReplayMode(): void
    {
        VCR::configure()
            ->setMode('none')
            ->enableRequestMatchers(['method', 'url', 'host', 'query_string', 'body', 'post_fields', 'headers']);
    }

    private function postStartSetup(): void
    {
        match ($this->mode) {
            Mode::FAKE => VCR::insertCassette('fake'),
            Mode::RECORD => VCR::insertCassette('recording'),
            Mode::REPLAY => VCR::insertCassette('replay'),
            default => VCR::insertCassette('fake'),
        };
    }

    private function registerFakeHooks(): void
    {
        $handler = fn (VcrRequest $request): VcrResponse => $this->handle($request);

        foreach (VCR::configure()->getLibraryHooks() as $hookClass) {
            /** @var \VCR\LibraryHooks\LibraryHook $hook */
            $hook = VCRFactory::get($hookClass);
            $hook->disable();
            $hook->enable($handler);
        }
    }

    private function registerReplayHooks(): void
    {
        $handler = function (VcrRequest $request): VcrResponse {
            /** @var Videorecorder $videorecorder */
            $videorecorder = VCRFactory::get(Videorecorder::class);
            try {
                return $videorecorder->handleRequest($request);
            } catch (LogicException $e) {
                throw ReplayMismatchError::forRequest($request, $e);
            }
        };

        foreach (VCR::configure()->getLibraryHooks() as $hookClass) {
            /** @var \VCR\LibraryHooks\LibraryHook $hook */
            $hook = VCRFactory::get($hookClass);
            $hook->disable();
            $hook->enable($handler);
        }
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
