<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Converts server identifiers into portable cassette file names.
 */
final class CassetteNameNormalizer
{
    /**
     * Normalize a name to lowercase filename-safe characters.
     */
    public function normalize(string $name): string
    {
        $normalized = strtolower(str_replace('\\', '-', $name));
        $normalized = preg_replace('/[^a-z0-9_.-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized === '' ? 'recording' : $normalized;
    }
}
