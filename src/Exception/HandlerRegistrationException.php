<?php

declare(strict_types=1);

namespace OasFake\Exception;

use ReflectionException;

/**
 * Thrown when a declarative server handler cannot be registered.
 */
final class HandlerRegistrationException extends OasFakeException
{
    /**
     * Create an exception that preserves the reflection failure.
     *
     * @param class-string $serverClass
     */
    public static function forServer(string $serverClass, ReflectionException $previous): self
    {
        return new self(
            sprintf('Unable to register declarative handlers for %s: %s', $serverClass, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
