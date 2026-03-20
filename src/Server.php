<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\SchemaNotFoundException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use ReflectionMethod;

class Server
{
    protected static string $SCHEMA = '';
    protected static string $MODE = 'fake';
    protected static string $CASSETTE_PATH = './cassettes';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;
    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    protected static array $FAKER_OPTIONS = [];

    /** @return list<MiddlewareInterface> */
    protected static function middleware(): array
    {
        return [];
    }

    private ?string $schema = null;
    private ?Mode $mode = null;
    private ?string $cassettePath = null;
    private ?bool $validateRequests = null;
    private ?bool $validateResponses = null;
    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}|null */
    private ?array $fakerOptions = null;
    /** @var list<MiddlewareInterface> */
    private array $additionalMiddleware = [];
    private StubMap $stubs;
    private ?Interceptor $interceptor = null;

    public function __construct()
    {
        $this->stubs = new StubMap();
        $this->registerMethodStubs();
    }

    // ---- Fluent configuration ----

    public function withSchema(string $schemaPath): static
    {
        $this->schema = $schemaPath;

        return $this;
    }

    public function withMode(Mode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function withCassettePath(string $path): static
    {
        $this->cassettePath = $path;

        return $this;
    }

    public function withRequestValidation(bool $enable = true): static
    {
        $this->validateRequests = $enable;

        return $this;
    }

    public function withResponseValidation(bool $enable = true): static
    {
        $this->validateResponses = $enable;

        return $this;
    }

    /** @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options */
    public function withFakerOptions(array $options): static
    {
        $this->fakerOptions = $options;

        return $this;
    }

    public function withMiddleware(MiddlewareInterface $middleware): static
    {
        $this->additionalMiddleware[] = $middleware;

        return $this;
    }

    // ---- Fluent stubs ----

    public function withStub(string $operationId, Stub $stub): static
    {
        $this->stubs->forOperation($operationId, $stub);

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     */
    public function withResponse(string $operationId, int $status, array|string $body, array $headers = []): static
    {
        return $this->withStub($operationId, Stub::response($status, $body, $headers));
    }

    public function withCallback(string $operationId, callable $callback): static
    {
        return $this->withStub($operationId, Stub::callback($callback));
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     */
    public function withPathResponse(string $path, string $method, int $status, array|string $body, array $headers = []): static
    {
        $this->stubs->forPath($path, $method, Stub::response($status, $body, $headers));

        return $this;
    }

    public function withPathCallback(string $path, string $method, callable $callback): static
    {
        $this->stubs->forPath($path, $method, Stub::callback($callback));

        return $this;
    }

    // ---- Lifecycle ----

    public function start(): void
    {
        if ($this->interceptor !== null && $this->interceptor->isRunning()) {
            return;
        }

        $schema = $this->resolveSchema();
        $this->interceptor = new Interceptor(
            mode: $this->resolveMode(),
            cassettePath: $this->resolveCassettePath(),
            schema: $schema,
            validator: new Validator($schema),
            faker: new Faker($schema, $this->resolveFakerOptions()),
            stubs: $this->stubs,
            validateRequests: $this->resolveValidateRequests(),
            validateResponses: $this->resolveValidateResponses(),
            middleware: $this->resolveMiddleware(),
        );

        $this->interceptor->start();
    }

    public function stop(): void
    {
        if ($this->interceptor !== null) {
            $this->interceptor->stop();
            $this->interceptor = null;
        }
    }

    public function isRunning(): bool
    {
        return $this->interceptor !== null && $this->interceptor->isRunning();
    }

    // ---- Private resolution: env -> fluent -> static -> default ----

    private function resolveMode(): Mode
    {
        $env = getenv('OAS_FAKE_MODE');
        if ($env !== false && $env !== '') {
            return Mode::fromString($env);
        }
        if ($this->mode !== null) {
            return $this->mode;
        }

        return Mode::fromString(static::$MODE);
    }

    private function resolveSchema(): Schema
    {
        $path = $this->schema ?? static::$SCHEMA;
        if ($path === '') {
            throw new SchemaNotFoundException('No schema configured. Set $SCHEMA or call withSchema().');
        }

        return Schema::fromFile($path);
    }

    private function resolveCassettePath(): string
    {
        $env = getenv('OAS_FAKE_CASSETTE_PATH');
        if ($env !== false && $env !== '') {
            return $env;
        }

        return $this->cassettePath ?? static::$CASSETTE_PATH;
    }

    private function resolveValidateRequests(): bool
    {
        return $this->resolveBoolEnv('OAS_FAKE_VALIDATE_REQUESTS', $this->validateRequests, static::$VALIDATE_REQUESTS);
    }

    private function resolveValidateResponses(): bool
    {
        return $this->resolveBoolEnv('OAS_FAKE_VALIDATE_RESPONSES', $this->validateResponses, static::$VALIDATE_RESPONSES);
    }

    private function resolveBoolEnv(string $envVar, ?bool $fluent, bool $static): bool
    {
        $env = getenv($envVar);
        if ($env !== false && $env !== '') {
            return filter_var($env, FILTER_VALIDATE_BOOLEAN);
        }

        return $fluent ?? $static;
    }

    /** @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    private function resolveFakerOptions(): array
    {
        return $this->fakerOptions ?? static::$FAKER_OPTIONS;
    }

    /** @return list<MiddlewareInterface> */
    private function resolveMiddleware(): array
    {
        return array_merge(static::middleware(), $this->additionalMiddleware);
    }

    // ---- Reflection-based stub registration ----

    private function registerMethodStubs(): void
    {
        $reflection = new ReflectionClass($this);
        $baseMethods = $this->getBaseMethods();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            if (in_array($method->getName(), $baseMethods, true)) {
                continue;
            }

            // Check for #[Route] attribute
            $routeAttr = $this->getRouteAttribute($method);
            $closure = $method->getClosure($this);
            if ($closure === null) {
                continue;
            }

            if ($routeAttr !== null) {
                $this->stubs->forPath(
                    $routeAttr->path,
                    $routeAttr->method,
                    Stub::callback($closure),
                );
            } else {
                // Method name = operationId
                $this->stubs->forOperation(
                    $method->getName(),
                    Stub::callback($closure),
                );
            }
        }
    }

    private function getRouteAttribute(ReflectionMethod $method): ?Route
    {
        $attributes = $method->getAttributes(Route::class);
        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /** @return list<string> */
    private function getBaseMethods(): array
    {
        return [
            'start', 'stop', 'isRunning',
            'withSchema', 'withMode', 'withCassettePath',
            'withRequestValidation', 'withResponseValidation',
            'withFakerOptions', 'withMiddleware',
            'withStub', 'withResponse', 'withCallback',
            'withPathResponse', 'withPathCallback',
        ];
    }
}
