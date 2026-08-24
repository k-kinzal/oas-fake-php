<?php

declare(strict_types=1);

namespace OasFake\Exception;

use Throwable;

/**
 * Thrown when configured OpenAPI content cannot be parsed into a schema.
 */
final class SchemaParseException extends OasFakeException
{
    /**
     * Create an exception that preserves the parser-specific cause.
     */
    public static function forSource(string $source, Throwable $previous): self
    {
        return new self(
            sprintf('Unable to parse OpenAPI schema from %s: %s', $source, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
