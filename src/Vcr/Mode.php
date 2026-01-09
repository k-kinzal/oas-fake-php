<?php

declare(strict_types=1);

namespace OasFakePHP\Vcr;

use InvalidArgumentException;

enum Mode: string
{
    case RECORD = 'record';
    case REPLAY = 'replay';
    case PASSTHROUGH = 'passthrough';

    private const ENV_VAR_NAME = 'OAS_FAKE_VCR_MODE';

    public static function fromEnvironment(): self
    {
        $value = getenv(self::ENV_VAR_NAME);

        if ($value === false || $value === '') {
            return self::REPLAY;
        }

        return self::fromString($value);
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'record' => self::RECORD,
            'replay' => self::REPLAY,
            'passthrough' => self::PASSTHROUGH,
            default => throw new InvalidArgumentException(
                sprintf(
                    'Invalid mode "%s". Valid modes are: %s',
                    $value,
                    implode(', ', array_column(self::cases(), 'value')),
                ),
            ),
        };
    }
}
