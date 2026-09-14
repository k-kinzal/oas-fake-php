<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Message\ResponseInterface;

/**
 * Captures the status, normalized headers, and body of a generated HTTP response.
 *
 * @visibility namespace
 */
final class ResponseSnapshotFactory
{
    /**
     * @return array{statusCode: int, headers: array<string, string>, rawBody: string}
     */
    public function create(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }

        return [
            'statusCode' => $response->getStatusCode(),
            'headers' => $headers,
            'rawBody' => (string) $response->getBody(),
        ];
    }
}
