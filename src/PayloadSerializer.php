<?php

declare(strict_types=1);

namespace OasFake;

use function explode;

use GuzzleHttp\Psr7\Query;

use function is_array;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use function strtolower;
use function trim;

/**
 * Serializes fake payload data for common OpenAPI media types.
 */
final class PayloadSerializer
{
    /**
     * Serialize generated payload data for the given media type.
     */
    public static function serialize(mixed $data, string $mediaType): string
    {
        $normalized = self::normalizeMediaType($mediaType);

        if (self::isJsonMediaType($normalized)) {
            return self::toJson($data);
        }

        if ($normalized === 'application/x-www-form-urlencoded') {
            return is_array($data) ? Query::build($data) : self::toText($data);
        }

        if (str_starts_with($normalized, 'text/')) {
            return self::toText($data);
        }

        return self::toJson($data);
    }

    /**
     * Return the preferred media type, favoring JSON when available.
     *
     * @param list<string> $mediaTypes
     */
    public static function preferredMediaType(array $mediaTypes): string
    {
        foreach ($mediaTypes as $mediaType) {
            if (self::normalizeMediaType($mediaType) === 'application/json') {
                return $mediaType;
            }
        }

        foreach ($mediaTypes as $mediaType) {
            if (self::isJsonMediaType(self::normalizeMediaType($mediaType))) {
                return $mediaType;
            }
        }

        return $mediaTypes[0] ?? 'application/json';
    }

    private static function toJson(mixed $data): string
    {
        if (is_array($data) || is_scalar($data) || $data === null) {
            return json_encode($data, JSON_THROW_ON_ERROR);
        }

        return json_encode(null, JSON_THROW_ON_ERROR);
    }

    private static function toText(mixed $data): string
    {
        if (is_scalar($data) || $data === null) {
            return (string) $data;
        }

        return self::toJson($data);
    }

    private static function normalizeMediaType(string $mediaType): string
    {
        return strtolower(trim(explode(';', $mediaType, 2)[0]));
    }

    /**
     * Check whether a media type is JSON or structured JSON.
     */
    public static function isJsonMediaType(string $mediaType): bool
    {
        $normalized = self::normalizeMediaType($mediaType);

        return $normalized === 'application/json' || str_ends_with($normalized, '+json');
    }
}
