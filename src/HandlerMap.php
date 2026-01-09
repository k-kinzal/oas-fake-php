<?php

declare(strict_types=1);

namespace OasFake;

use function strtoupper;

/**
 * Lookup table for handlers, indexed by operation ID or path/method pair.
 */
final class HandlerMap
{
    /**
     * @var array<string, Handler>
     */
    private array $byOperationId = [];

    /**
     * @var array<string, Handler>
     */
    private array $byPathMethod = [];

    /**
     * Register a handler for an operation ID.
     *
     * @param string $operationId The OpenAPI operationId
     * @param Handler $handler The handler to register
     */
    public function forOperation(string $operationId, Handler $handler): void
    {
        $this->byOperationId[$operationId] = $handler;
    }

    /**
     * Register a handler for a path and HTTP method pair.
     *
     * @param string $path The URL path pattern
     * @param string $method The HTTP method
     * @param Handler $handler The handler to register
     */
    public function forPath(string $path, string $method, Handler $handler): void
    {
        $key = strtoupper($method) . ':' . $path;
        $this->byPathMethod[$key] = $handler;
    }

    /**
     * Find a matching handler by operation ID or path/method pair.
     *
     * Looks up by operation ID first, then falls back to path/method matching.
     *
     * @param string $operationId The OpenAPI operationId (empty string to skip)
     * @param string $path The request path
     * @param string $method The HTTP method
     *
     * @return Handler|null The matching handler, or null if none found
     */
    public function find(string $operationId, string $path, string $method): ?Handler
    {
        if ($operationId !== '' && isset($this->byOperationId[$operationId])) {
            return $this->byOperationId[$operationId];
        }

        $key = strtoupper($method) . ':' . $path;

        return $this->byPathMethod[$key] ?? null;
    }

    /**
     * Remove all registered handlers.
     */
    public function clear(): void
    {
        $this->byOperationId = [];
        $this->byPathMethod = [];
    }

    /**
     * Check whether no handlers are registered.
     *
     * @return bool True if no handlers are registered
     */
    public function isEmpty(): bool
    {
        return $this->byOperationId === [] && $this->byPathMethod === [];
    }
}
