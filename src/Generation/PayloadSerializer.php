<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Query;

use function is_array;

use JsonException;

/**
 * Serializes fake payload data for common OpenAPI media types.
 *
 * @visibility namespace
 */
final class PayloadSerializer
{
    /**
     * Serialize generated payload data for the given media type.
     *
     * @throws JsonException when the payload cannot be encoded
     */
    public static function serialize(mixed $data, string $mediaType): string
    {
        $codec = new PayloadCodec();
        $normalized = $codec->normalizeMediaType($mediaType);

        if ($codec->isJsonMediaType($normalized)) {
            return $codec->encodeJson($data);
        }

        if ($normalized === 'application/x-www-form-urlencoded') {
            return is_array($data) ? Query::build($data) : $codec->encodeText($data);
        }

        if (str_starts_with($normalized, 'text/')) {
            return $codec->encodeText($data);
        }

        return $codec->encodeJson($data);
    }

    /**
     * Return the preferred media type, favoring JSON when available.
     *
     * @param list<string> $mediaTypes
     */
    public static function preferredMediaType(array $mediaTypes): string
    {
        $codec = new PayloadCodec();
        foreach ($mediaTypes as $mediaType) {
            if ($codec->normalizeMediaType($mediaType) === 'application/json') {
                return $mediaType;
            }
        }

        foreach ($mediaTypes as $mediaType) {
            if ($codec->isJsonMediaType($mediaType)) {
                return $mediaType;
            }
        }

        return $mediaTypes[0] ?? 'application/json';
    }

    /**
     * Check whether a media type is JSON or structured JSON.
     */
    public static function isJsonMediaType(string $mediaType): bool
    {
        return (new PayloadCodec())->isJsonMediaType($mediaType);
    }
}
