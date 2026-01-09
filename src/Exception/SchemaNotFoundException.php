<?php

declare(strict_types=1);

namespace OasFake\Exception;

/**
 * Thrown when an OpenAPI schema file cannot be found.
 */
final class SchemaNotFoundException extends OasFakeException
{
    /**
     * Create an exception for a missing schema file.
     *
     * @param string $path The file path that was not found
     */
    public static function forPath(string $path): self
    {
        return new self(sprintf('OpenAPI schema file not found: %s', $path));
    }
}
