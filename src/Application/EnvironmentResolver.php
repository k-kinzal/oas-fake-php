<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Reads non-empty environment overrides with explicit scalar semantics.
 *
 * @visibility namespace
 */
final class EnvironmentResolver
{
    /**
     * Return a non-empty environment value or the supplied default.
     */
    public function string(string $name, string $default): string
    {
        $value = getenv($name);

        return $value !== false && $value !== '' ? $value : $default;
    }

    /**
     * Return a boolean environment value or the supplied default.
     */
    public function boolean(string $name, bool $default): bool
    {
        $value = getenv($name);

        return $value !== false && $value !== '' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $default;
    }
}
