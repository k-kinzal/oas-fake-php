<?php

declare(strict_types=1);

namespace OasFake;

use Override;
use VCR\Response;

/**
 * Preserves false-like response bodies at the PHP-VCR representation boundary.
 *
 * @visibility namespace
 */
final class LosslessVcrResponse extends Response
{
    /**
     * Copy the vendor response's protected representation without its lossy body accessor.
     */
    public static function fromResponse(Response $response): self
    {
        $copy = new self(
            ['code' => (string) $response->statusCode, 'message' => $response->statusMessage],
            $response->headers,
            $response->body,
            $response->curlInfo,
        );
        $copy->httpVersion = $response->httpVersion;

        return $copy;
    }

    /**
     * Return the original bytes, treating only a missing body as empty.
     */
    #[Override]
    public function getBody(): string
    {
        return $this->body ?? '';
    }

    /**
     * Retain PHP-VCR's cassette encoding while preserving a body equal to "0".
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $recording = parent::toArray();
        if ($this->body === '0' && !array_key_exists('body', $recording)) {
            $recording['body'] = '0';
        }

        return $recording;
    }
}
