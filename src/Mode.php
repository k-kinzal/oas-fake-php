<?php

declare(strict_types=1);

namespace OasFake;

use InvalidArgumentException;

/**
 * Operating mode for fake servers: FAKE, RECORD, or REPLAY.
 */
final class Mode
{
    /**
     * Generate schema-compliant fake responses in-process.
     */
    public const FAKE = 'fake';

    /**
     * Record generated request/response pairs to a cassette.
     */
    public const RECORD = 'record';

    /**
     * Replay responses from a previously recorded cassette.
     */
    public const REPLAY = 'replay';

    private const ENV_VAR = 'OAS_FAKE_MODE';

    private function __construct(private string $value)
    {
    }

    /**
     * Return a normalized Mode instance from a string or existing Mode.
     */
    public static function from(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::fromString($value);
    }

    /**
     * Resolve the mode from the OAS_FAKE_MODE environment variable.
     *
     * Falls back to FAKE if the variable is not set.
     */
    public static function fromEnvironment(): self
    {
        $value = getenv(self::ENV_VAR);

        if ($value === false || $value === '') {
            return new self(self::FAKE);
        }

        return self::fromString($value);
    }

    /**
     * Parse a mode from a string value.
     *
     * @param string $value The mode string (case-insensitive)
     *
     * @throws InvalidArgumentException If the value does not match a valid mode
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'fake' => new self(self::FAKE),
            'record' => new self(self::RECORD),
            'replay' => new self(self::REPLAY),
            default => throw new InvalidArgumentException(
                sprintf('Invalid mode "%s". Valid modes are: %s', $value, implode(', ', [self::FAKE, self::RECORD, self::REPLAY])),
            ),
        };
    }

    /**
     * Return the canonical string value.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Check whether the mode records generated responses.
     */
    public function isRecord(): bool
    {
        return $this->value === self::RECORD;
    }

    /**
     * Check whether the mode replays recorded responses.
     */
    public function isReplay(): bool
    {
        return $this->value === self::REPLAY;
    }
}
