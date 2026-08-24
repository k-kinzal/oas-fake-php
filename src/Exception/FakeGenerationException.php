<?php

declare(strict_types=1);

namespace OasFake\Exception;

use Throwable;

/**
 * Thrown when schema-backed request or response generation fails.
 */
final class FakeGenerationException extends OasFakeException
{
    /**
     * Create an exception for a failed OpenAPI operation generation.
     */
    public static function forOperation(string $operation, Throwable $previous): self
    {
        return new self(
            sprintf('Unable to generate fake data for %s: %s', $operation, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
