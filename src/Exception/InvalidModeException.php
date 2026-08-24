<?php

declare(strict_types=1);

namespace OasFake\Exception;

/**
 * Thrown when configuration selects an unsupported server mode.
 */
final class InvalidModeException extends OasFakeException
{
    /**
     * Create an exception that names the invalid value and supported modes.
     *
     * @param list<string> $supportedModes
     */
    public static function forValue(string $value, array $supportedModes): self
    {
        return new self(sprintf('Invalid mode "%s". Valid modes are: %s', $value, implode(', ', $supportedModes)));
    }
}
