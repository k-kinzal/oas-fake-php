<?php

declare(strict_types=1);

namespace OasFake;

use InvalidArgumentException;

/**
 * Operating mode for fake servers: FAKE, RECORD, or REPLAY.
 */
final class Mode
{
    public const FAKE = 'fake';
    public const RECORD = 'record';
    public const REPLAY = 'replay';

    private const ENV_VAR = 'OAS_FAKE_MODE';

    /**
     * Resolve the mode from the OAS_FAKE_MODE environment variable.
     *
     * Falls back to FAKE if the variable is not set.
     *
     * @return string The resolved mode
     */
    public static function fromEnvironment(): string
    {
        $value = getenv(self::ENV_VAR);

        if ($value === false || $value === '') {
            return self::FAKE;
        }

        return self::fromString($value);
    }

    /**
     * Parse a mode from a string value.
     *
     * @param string $value The mode string (case-insensitive)
     *
     * @throws InvalidArgumentException If the value does not match a valid mode
     *
     * @return string The parsed mode
     */
    public static function fromString(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'fake' => self::FAKE,
            'record' => self::RECORD,
            'replay' => self::REPLAY,
            default => throw new InvalidArgumentException(
                sprintf('Invalid mode "%s". Valid modes are: %s', $value, implode(', ', [self::FAKE, self::RECORD, self::REPLAY])),
            ),
        };
    }
}
