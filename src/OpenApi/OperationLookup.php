<?php

declare(strict_types=1);

namespace OasFake;

use function strtolower;

/**
 * Indexes OpenAPI operations for lookup by operationId or path/method pair.
 */
final class OperationLookup
{
    private RequestPathMatcher $requestPathMatcher;

    /**
     * @var array<string, OperationInfo> keyed by operationId
     */
    private array $byOperationId = [];

    /**
     * @var array<string, OperationInfo> keyed by "METHOD:/path"
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
        $this->requestPathMatcher = new RequestPathMatcher();
    }

    /**
     * Find an operation by its operationId.
     *
     * @param string $operationId The OpenAPI operationId
     */
    public function findByOperationId(string $operationId): ?OperationInfo
    {
        return $this->byOperationId[$operationId] ?? null;
    }

    /**
     * Find an operation by path pattern and HTTP method.
     *
     * @param string $path The OpenAPI path pattern
     * @param string $method The HTTP method (case-insensitive)
     */
    public function findByPathAndMethod(string $path, string $method): ?OperationInfo
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
    public function findByRequestPathAndMethod(string $path, string $method): ?OperationInfo
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

            if ($this->requestPathMatcher->matches($info->pathPattern, $path)) {
                return $info;
            }
        }

        return null;
    }
}
