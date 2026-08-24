<?php

declare(strict_types=1);

namespace OasFake\Exception;

use JsonException;

/**
 * Thrown when a configured handler cannot produce its response.
 */
final class HandlerResolutionException extends OasFakeException
{
    /**
     * Create an exception for a fixed response body that cannot be encoded.
     */
    public static function forBody(int $statusCode, JsonException $previous): self
    {
        return new self(
            sprintf('Unable to encode handler response body for status %d: %s', $statusCode, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
