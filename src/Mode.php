<?php

declare(strict_types=1);

namespace OasFake;

use InvalidArgumentException;

enum Mode: string
{
    case FAKE = 'fake';
    case RECORD = 'record';
    case REPLAY = 'replay';

    private const ENV_VAR = 'OAS_FAKE_MODE';

    public static function fromEnvironment(): self
    {
        $value = getenv(self::ENV_VAR);

        if ($value === false || $value === '') {
            return self::FAKE;
        }

        return self::fromString($value);
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'fake' => self::FAKE,
            'record' => self::RECORD,
            'replay' => self::REPLAY,
            default => throw new InvalidArgumentException(
                sprintf('Invalid mode "%s". Valid modes are: %s', $value, implode(', ', array_column(self::cases(), 'value'))),
            ),
        };
    }
}
