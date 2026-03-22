<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\SchemaNotFoundException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use ReflectionMethod;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;
use VCR\VCRFactory;

/**
 * Base server class providing fluent configuration, handler management, and lifecycle control.
 *
 * Subclass this to define a persistent server configuration with static properties,
 * or use the fluent API via OasFake::start() to configure on the fly.
 */
class Server
{
    protected static string $SCHEMA = '';
    protected static string $MODE = 'fake';
    protected static string $CASSETTE_PATH = './cassettes';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;
    /**
     * @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    protected static array $FAKER_OPTIONS = [];

    /**
     * @return list<MiddlewareInterface>
     */
    protected static function middleware(): array
    {
        return [];
    }

    private ?string $schema = null;
    private ?string $mode = null;
    private ?string $cassettePath = null;
    private ?bool $validateRequests = null;
    private ?bool $validateResponses = null;
    /**
     * @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}|null
     */
    private ?array $fakerOptions = null;

    /**
     * @var list<MiddlewareInterface>
     */
    private array $additionalMiddleware = [];
    private HandlerMap $handlers;
    private ?Interceptor $interceptor = null;
    private ?Schema $resolvedSchema = null;
    private bool $vcrStarted = false;

    public function __construct()
    {
        $this->handlers = new HandlerMap();
        $this->registerMethodHandlers();
    }

    /**
     * Set the OpenAPI schema file path.
     *
     * @param string $schemaPath Path to the OpenAPI JSON or YAML file
     */
    public function withSchema(string $schemaPath): static
    {
        $this->schema = $schemaPath;

        return $this;
    }

    /**
     * Set the operating mode.
     *
     * @param string $mode The mode to use (FAKE, RECORD, or REPLAY)
     */
    public function withMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Set the directory path for cassette files.
     *
     * @param string $path Directory path for cassette storage
     */
    public function withCassettePath(string $path): static
    {
        $this->cassettePath = $path;

        return $this;
    }

    /**
     * Enable or disable request validation against the OpenAPI schema.
     *
     * @param bool $enable Whether to validate incoming requests
     */
    public function withRequestValidation(bool $enable = true): static
    {
        $this->validateRequests = $enable;

        return $this;
    }

    /**
     * Enable or disable response validation against the OpenAPI schema.
     *
     * @param bool $enable Whether to validate outgoing responses
     */
    public function withResponseValidation(bool $enable = true): static
    {
        $this->validateResponses = $enable;

        return $this;
    }

    /**
     * Set the options for the OpenAPI faker.
     *
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function withFakerOptions(array $options): static
    {
        $this->fakerOptions = $options;

        return $this;
    }

    /**
     * Add a PSR-15 middleware to the processing pipeline.
     *
     * @param MiddlewareInterface $middleware Middleware to append
     */
    public function withMiddleware(MiddlewareInterface $middleware): static
    {
        $this->additionalMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Register a handler for a specific operation ID.
     *
     * @param string $operationId The OpenAPI operationId
     * @param Handler $handler The handler to register
     */
    public function withHandler(string $operationId, Handler $handler): static
    {
        $this->handlers->forOperation($operationId, $handler);

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     */
    public function withResponse(string $operationId, int $status, array|string $body, array $headers = []): static
    {
        return $this->withHandler($operationId, Handler::response($status, $body, $headers));
    }

    /**
     * Register a callback handler for a specific operation ID.
     *
     * @param string $operationId The OpenAPI operationId
     * @param callable $callback Callback that receives the request and returns a response
     */
    public function withCallback(string $operationId, callable $callback): static
    {
        return $this->withHandler($operationId, Handler::callback($callback));
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     */
    public function withPathResponse(string $path, string $method, int $status, array|string $body, array $headers = []): static
    {
        $this->handlers->forPath($path, $method, Handler::response($status, $body, $headers));

        return $this;
    }

    /**
     * Register a callback handler for a specific path and HTTP method.
     *
     * @param string $path The URL path pattern
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param callable $callback Callback that receives the request and returns a response
     */
    public function withPathCallback(string $path, string $method, callable $callback): static
    {
        $this->handlers->forPath($path, $method, Handler::callback($callback));

        return $this;
    }

    /**
     * Build the interceptor without starting VCR.
     *
     * Creates the Interceptor and initializes cassettes for RECORD/REPLAY modes.
     * Used by ServerRegistry which manages VCR lifecycle externally.
     */
    public function buildInterceptor(): void
    {
        if ($this->interceptor !== null && $this->interceptor->isRunning()) {
            return;
        }

        $schema = $this->resolveSchema();
        $this->resolvedSchema = $schema;
        $this->interceptor = new Interceptor(
            mode: $this->resolveMode(),
            cassettePath: $this->resolveCassettePath(),
            schema: $schema,
            validator: new Validator($schema),
            fakerOptions: $this->resolveFakerOptions(),
            handlers: $this->handlers,
            validateRequests: $this->resolveValidateRequests(),
            validateResponses: $this->resolveValidateResponses(),
            middleware: $this->resolveMiddleware(),
        );

        $this->interceptor->start();
    }

    /**
     * Start the fake server: build interceptor and activate HTTP hooks.
     */
    public function start(): void
    {
        $this->buildInterceptor();
        $this->startVcr();
    }

    /**
     * Stop the fake server and release the interceptor.
     */
    public function stop(): void
    {
        if ($this->interceptor !== null) {
            $this->interceptor->stop();
            $this->interceptor = null;
        }

        if ($this->vcrStarted) {
            VCR::turnOff();
            $this->vcrStarted = false;
        }
    }

    /**
     * Check whether the server is currently running.
     *
     * @return bool True if the interceptor is active
     */
    public function isRunning(): bool
    {
        return $this->interceptor !== null && $this->interceptor->isRunning();
    }

    /**
     * Return the active interceptor instance.
     *
     * @return Interceptor|null The interceptor, or null if the server is not running
     */
    public function interceptor(): ?Interceptor
    {
        return $this->interceptor;
    }

    /**
     * Return the resolved OpenAPI schema.
     */
    public function schema(): Schema
    {
        if ($this->resolvedSchema !== null) {
            return $this->resolvedSchema;
        }

        return $this->resolveSchema();
    }

    /**
     * Return the resolved faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->resolveFakerOptions();
    }

    /**
     * Return the server URLs from the resolved schema.
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        if ($this->resolvedSchema !== null) {
            return $this->resolvedSchema->serverUrls();
        }

        return $this->resolveSchema()->serverUrls();
    }

    public function resolveMode(): string
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

    private function startVcr(): void
    {
        if ($this->vcrStarted) {
            return;
        }

        $this->configureVcr();
        VCR::turnOn();
        VCR::insertCassette('oas-fake');
        $this->registerHooks();
        $this->vcrStarted = true;
    }

    private function configureVcr(): void
    {
        VCR::configure()
            ->setCassettePath($this->resolveCassettePath())
            ->setStorage('json')
            ->setMode('none')
            ->enableLibraryHooks(['curl', 'stream_wrapper']);
    }

    private function registerHooks(): void
    {
        $interceptor = $this->interceptor;
        if ($interceptor === null) {
            return;
        }

        $mode = $this->resolveMode();
        $handler = match ($mode) {
            Mode::REPLAY => fn (VcrRequest $req): VcrResponse => $interceptor->replay($req),
            default => fn (VcrRequest $req): VcrResponse => $interceptor->handle($req),
        };

        foreach (VCR::configure()->getLibraryHooks() as $hookClass) {
            /** @var \VCR\LibraryHooks\LibraryHook $hook */
            $hook = VCRFactory::get($hookClass);
            $hook->disable();
            $hook->enable($handler);
        }
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

    /**
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    private function resolveFakerOptions(): array
    {
        return $this->fakerOptions ?? static::$FAKER_OPTIONS;
    }

    /**
     * @return list<MiddlewareInterface>
     */
    private function resolveMiddleware(): array
    {
        return array_merge(static::middleware(), $this->additionalMiddleware);
    }

    private function registerMethodHandlers(): void
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

            $routeAttr = $this->getRouteAttribute($method);
            $closure = $method->getClosure($this);
            if ($closure === null) {
                continue;
            }

            if ($routeAttr !== null) {
                $this->handlers->forPath(
                    $routeAttr->path,
                    $routeAttr->method,
                    Handler::callback($closure),
                );
            } else {
                $this->handlers->forOperation(
                    $method->getName(),
                    Handler::callback($closure),
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

    /**
     * @return list<string>
     */
    private function getBaseMethods(): array
    {
        return [
            'start', 'stop', 'isRunning',
            'interceptor', 'serverUrls', 'resolveMode',
            'schema', 'fakerOptions', 'buildInterceptor',
            'withSchema', 'withMode', 'withCassettePath',
            'withRequestValidation', 'withResponseValidation',
            'withFakerOptions', 'withMiddleware',
            'withHandler', 'withResponse', 'withCallback',
            'withPathResponse', 'withPathCallback',
        ];
    }
}
