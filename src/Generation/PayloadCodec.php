<?php

declare(strict_types=1);

namespace OasFake;

use function explode;
use function is_array;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;

use function strtolower;
use function trim;

/**
 * Encodes generated payloads and normalizes their media types.
 *
 * @visibility namespace
 */
final class PayloadCodec
{
    /**
     * Encode JSON-compatible generated data.
     *
     * @template TPayload
     *
     * @param TPayload $data
     *
     * @throws JsonException when the payload cannot be encoded
     */
    public function encodeJson(mixed $data): string
    {
        if (is_array($data) || is_scalar($data) || $data === null) {
            return json_encode($data, JSON_THROW_ON_ERROR);
        }

        return json_encode(null, JSON_THROW_ON_ERROR);
    }

    /**
     * Encode scalar text, falling back to JSON for structured data.
     *
     * @template TPayload
     *
     * @param TPayload $data
     *
     * @throws JsonException when structured data cannot be encoded
     */
    public function encodeText(mixed $data): string
    {
        if (is_scalar($data) || $data === null) {
            return (string) $data;
        }

        return $this->encodeJson($data);
    }

    /**
     * Normalize a media type by removing parameters and folding its case.
     */
    public function normalizeMediaType(string $mediaType): string
    {
        return strtolower(trim(explode(';', $mediaType)[0]));
    }

    /**
     * Report whether a media type represents JSON.
     */
    public function isJsonMediaType(string $mediaType): bool
    {
        $normalized = $this->normalizeMediaType($mediaType);

        return $normalized === 'application/json' || str_ends_with($normalized, '+json');
    }
}
