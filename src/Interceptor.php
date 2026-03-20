<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

use function is_string;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function strtolower;

use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;

final class Interceptor
{
    private bool $running = false;

    private readonly Converter $converter;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly Mode $mode,
        private readonly string $cassettePath,
        private readonly Schema $schema,
        private readonly Validator $validator,
        private readonly Faker $faker,
        private readonly StubMap $stubs,
        private readonly bool $validateRequests,
        private readonly bool $validateResponses,
        private readonly array $middleware = [],
    ) {
        $this->converter = new Converter();
    }

    public function start(): void
    {
        if ($this->running) {
            return;
        }

        $this->configureVcr();
        VCR::turnOn();
        $this->postStartSetup();
        $this->running = true;
    }

    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        VCR::turnOff();
        $this->running = false;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function handle(VcrRequest $vcrRequest): VcrResponse
    {
        // 1. Convert VcrRequest to PSR-7
        $psrRequest = $this->converter->requestToPsr7($vcrRequest);

        // 2. Validate request (if enabled)
        $operation = null;
        if ($this->validateRequests) {
            $operation = $this->validator->validateRequest($psrRequest);
        }

        // 3. Resolve operationId from schema
        $operationId = $this->findOperationId($psrRequest);
        $path = $psrRequest->getUri()->getPath();
        $method = $psrRequest->getMethod();

        // 4. Find stub or generate faker response
        $stub = $this->stubs->find($operationId ?? '', $path, $method);

        if ($stub !== null) {
            $fakerDefault = null;
            if ($operation !== null) {
                $fakerDefault = $this->faker->response($operation);
            }
            $response = $stub->resolve($psrRequest, $fakerDefault);
        } elseif ($operation !== null) {
            $response = $this->faker->response($operation);
        } else {
            $response = $this->faker->responseForPath($path, $method);
        }

        // 5. Run user PSR-15 middleware (if any)
        $response = $this->runMiddleware($psrRequest, $response);

        // 6. Validate response (if enabled)
        if ($this->validateResponses && $operation !== null) {
            $this->validator->validateResponse($operation, $response);
        }

        // 7. Convert PSR-7 to VcrResponse
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
            ->enableRequestMatchers(['method', 'url']);
    }

    private function postStartSetup(): void
    {
        match ($this->mode) {
            Mode::FAKE => VCR::insertCassette('fake'),
            Mode::RECORD => VCR::insertCassette('recording'),
            Mode::REPLAY => VCR::insertCassette('replay'),
        };
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

        // Chain middleware in reverse order
        $chain = $handler;
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $chain;
            $chain = new class ($middleware, $next) implements RequestHandlerInterface {
                public function __construct(
                    private readonly MiddlewareInterface $middleware,
                    private readonly RequestHandlerInterface $next,
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
