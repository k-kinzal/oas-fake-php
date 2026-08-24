<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Base server class providing fluent configuration, handler management, and lifecycle control.
 *
 * Subclass this to define a persistent server configuration with static properties,
 * or use the fluent API via OasFake::start() to configure on the fly.
 */
class Server
{
    use ServerMiddleware;

    protected static string $SCHEMA = '';
    protected static string $MODE = 'fake';
    protected static string $CASSETTE_PATH = './cassettes';
    protected static string $CASSETTE_NAME = '';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;
    /**
     * @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    protected static array $FAKER_OPTIONS = [];

    private HandlerMap $handlers;
    private ?Schema $resolvedSchema = null;
    private ServerConfiguration $configuration;
    private ServerLifecycle $lifecycle;

    /**
     * Create a server instance.
     */
    public function __construct()
    {
        $this->configuration = new ServerConfiguration();
        $this->handlers = new HandlerMap();
        $this->lifecycle = new ServerLifecycle();
    }

    /**
     * Set the OpenAPI schema file path.
     *
     * @param string $schemaPath Path to the OpenAPI JSON or YAML file
     */
    public function withSchema(string $schemaPath): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setSchema($schemaPath);
        $this->resolvedSchema = null;

        return $this;
    }

    /**
     * Set the operating mode.
     *
     * @param Mode|string $mode The mode to use (FAKE, RECORD, or REPLAY)
     */
    public function withMode(string|Mode $mode): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setMode($mode);

        return $this;
    }

    /**
     * Set the directory path for cassette files.
     *
     * @param string $path Directory path for cassette storage
     */
    public function withCassettePath(string $path): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setCassettePath($path);

        return $this;
    }

    /**
     * Set the cassette file name used for RECORD and REPLAY modes.
     */
    public function withCassetteName(string $name): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setCassetteName($name);

        return $this;
    }

    /**
     * Enable or disable request validation against the OpenAPI schema.
     *
     * @param bool $enable Whether to validate incoming requests
     */
    public function withRequestValidation(bool $enable = true): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setRequestValidation($enable);

        return $this;
    }

    /**
     * Enable or disable response validation against the OpenAPI schema.
     *
     * @param bool $enable Whether to validate outgoing responses
     */
    public function withResponseValidation(bool $enable = true): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setResponseValidation($enable);

        return $this;
    }

    /**
     * Set the options for the OpenAPI faker.
     *
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function withFakerOptions(array $options): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setFakerOptions($options);

        return $this;
    }

    /**
     * Add a PSR-15 middleware to the processing pipeline.
     *
     * @param MiddlewareInterface $middleware Middleware to append
     */
    public function withMiddleware(MiddlewareInterface $middleware): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->addMiddleware($middleware);

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
        $this->lifecycle->assertConfigurable();
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
        $this->lifecycle->assertConfigurable();
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
        $this->lifecycle->assertConfigurable();
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
        if ($this->lifecycle->isRunning()) {
            return;
        }

        $schema = $this->configuration->schema(static::$SCHEMA);
        $this->resolvedSchema = $schema;
        $handlers = clone $this->handlers;
        (new DeclarativeHandlerRegistrar())->register($this, $handlers, $schema);

        $interceptor = (new InterceptorFactory())->create(
            new ServerOptions(
                schema: $schema,
                mode: $this->resolveMode(),
                cassettePath: $this->configuration->cassettePath(static::$CASSETTE_PATH),
                validateRequests: $this->configuration->requestValidation(static::$VALIDATE_REQUESTS),
                validateResponses: $this->configuration->responseValidation(static::$VALIDATE_RESPONSES),
                fakerOptions: $this->fakerOptions(),
                middleware: $this->configuration->middleware(static::middleware()),
                cassetteName: $this->configuration->cassetteName(static::$CASSETTE_NAME, static::class),
            ),
            $handlers,
        );
        $interceptor->start();
        $this->lifecycle->replaceInterceptor($interceptor);
    }

    /**
     * Start the fake server through the shared OasFake registry.
     */
    public function start(): void
    {
        OasFake::start($this);
    }

    /**
     * Stop the fake server and release the interceptor.
     */
    public function stop(): void
    {
        $registration = $this->lifecycle->registration();
        if ($registration !== null) {
            $registration['registry']->unregister($registration['key']);

            return;
        }

        $this->lifecycle->stopInterceptor();
    }

    /**
     * Check whether the server is currently running.
     */
    public function isRunning(): bool
    {
        return $this->lifecycle->isRunning();
    }

    /**
     * Return the active interceptor instance.
     */
    public function interceptor(): ?Interceptor
    {
        return $this->lifecycle->interceptor();
    }

    /**
     * Return the resolved OpenAPI schema.
     */
    public function schema(): Schema
    {
        if ($this->resolvedSchema !== null) {
            return $this->resolvedSchema;
        }

        return $this->configuration->schema(static::$SCHEMA);
    }

    /**
     * Return the resolved faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->configuration->fakerOptions(static::$FAKER_OPTIONS);
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

        return $this->configuration->schema(static::$SCHEMA)->serverUrls();
    }

    /**
     * Resolve the active mode from environment, fluent override, or static defaults.
     */
    public function resolveMode(): Mode
    {
        return $this->configuration->mode(static::$MODE);
    }

    /**
     * Register this server with a registry that owns the shared VCR lifecycle.
     */
    public function registerInRegistry(ServerRegistry $registry, string $key): void
    {
        $this->lifecycle->register($registry, $key);
    }

    /**
     * Unregister this server from a registry and stop its interceptor.
     */
    public function unregisterFromRegistry(ServerRegistry $registry, string $key): void
    {
        $this->lifecycle->unregister($registry, $key);
    }
}
