<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;

/**
 * Base server class providing fluent configuration, handler management, and lifecycle control.
 *
 * Subclass this to define a persistent server configuration with static properties,
 * or use the fluent API via OasFake::start() to configure on the fly.
 *
 * @visibility public
 *
 * @example Configuring the fake mode fluently
 *     $server = (new \OasFake\Server())->withMode(\OasFake\Mode::FAKE);
 *     $server->resolveMode()->value() // => 'fake'
 */
class Server
{
    use ServerMiddleware;
    use ServerRuntimeState;

    /**
     * Set the OpenAPI schema file path.
     *
     * @param string $schemaPath Path to the OpenAPI JSON or YAML file
     */
    public function withSchema(string $schemaPath): static
    {
        $this->lifecycle->assertConfigurable();
        $this->configuration->setSchema($schemaPath);
        $this->runtime->forgetSchema();

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
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws ReflectionException when a declarative handler cannot be bound
     */
    public function buildInterceptor(): void
    {
        if ($this->lifecycle->isRunning()) {
            return;
        }

        $this->lifecycle->replaceInterceptor($this->runtime->build($this));
    }

    /**
     * Start the fake server through the shared OasFake registry.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws ReflectionException when a declarative handler cannot be bound
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
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     */
    public function schema(): Schema
    {
        return $this->runtime->schema();
    }

    /**
     * Return the resolved faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->runtime->fakerOptions();
    }

    /**
     * Return the server URLs from the resolved schema.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        return $this->runtime->serverUrls();
    }

    /**
     * Resolve the active mode from environment, fluent override, or static defaults.
     */
    public function resolveMode(): Mode
    {
        return $this->runtime->mode();
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
