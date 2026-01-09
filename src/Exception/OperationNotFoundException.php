<?php

declare(strict_types=1);

namespace OasFake\Exception;

/**
 * Thrown when an OpenAPI operation cannot be found by operationId or path/method.
 */
final class OperationNotFoundException extends OasFakeException
{
    /**
     * Create an exception for a missing operation ID.
     *
     * @param string $operationId The operationId that was not found
     */
    public static function forOperationId(string $operationId): self
    {
        return new self(sprintf('Operation not found: %s', $operationId));
    }

    /**
     * Create an exception for a missing path/method combination.
     *
     * @param string $path The request path
     * @param string $method The HTTP method
     */
    public static function forPathAndMethod(string $path, string $method): self
    {
        return new self(sprintf('Operation not found for %s %s', strtoupper($method), $path));
    }
}
