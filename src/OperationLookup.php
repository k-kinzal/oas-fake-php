<?php

declare(strict_types=1);

namespace OasFake;

use function preg_match;
use function preg_quote;
use function preg_replace;
use function strtolower;

/**
 * Indexes OpenAPI operations for lookup by operationId or path/method pair.
 */
final class OperationLookup
{
    /**
     * @var array<string, OperationDefinition> keyed by operationId
     */
    private array $byOperationId = [];

    /**
     * @var array<string, OperationDefinition> keyed by "METHOD:/path"
     */
    private array $byPathMethod = [];

    /**
     * Build lookup indexes for every operation in the schema.
     */
    public function __construct(Schema $schema)
    {
        $indexes = (new OperationIndexBuilder())->build($schema);
        $this->byOperationId = $indexes['byOperationId'];
        $this->byPathMethod = $indexes['byPathMethod'];
    }

    /**
     * Find an operation by its operationId.
     *
     * @param string $operationId The OpenAPI operationId
     */
    public function findByOperationId(string $operationId): ?OperationDefinition
    {
        return $this->byOperationId[$operationId] ?? null;
    }

    /**
     * Find an operation by path pattern and HTTP method.
     *
     * @param string $path The OpenAPI path pattern
     * @param string $method The HTTP method (case-insensitive)
     */
    public function findByPathAndMethod(string $path, string $method): ?OperationDefinition
    {
        $key = strtolower($method) . ':' . $path;

        return $this->byPathMethod[$key] ?? null;
    }

    /**
     * Find an operation by an actual request path and HTTP method.
     *
     * Exact OpenAPI paths are preferred before templated paths.
     *
     * @param string $path The request path, for example "/pets/123"
     * @param string $method The HTTP method (case-insensitive)
     */
    public function findByRequestPathAndMethod(string $path, string $method): ?OperationDefinition
    {
        $normalizedMethod = strtolower($method);
        $exact = $this->findByPathAndMethod($path, $normalizedMethod);
        if ($exact !== null) {
            return $exact;
        }

        foreach ($this->byPathMethod as $key => $info) {
            if (!str_starts_with($key, $normalizedMethod . ':')) {
                continue;
            }

            if ($this->matchesPath($info->pathPattern, $path)) {
                return $info;
            }
        }

        return null;
    }

    /**
     * Check whether a request path satisfies an OpenAPI templated path.
     */
    public function matchesPath(string $pattern, string $path): bool
    {
        $quoted = preg_quote($pattern, '#');
        $regex = preg_replace('#\\\\\{[^}/]+\\\\\}#', '[^/]+', $quoted);
        if ($regex === null) {
            return false;
        }

        return preg_match('#^' . $regex . '$#', $path) === 1;
    }
}
