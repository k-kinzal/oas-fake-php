<?php

declare(strict_types=1);

namespace OasFake;

use function array_values;
use function count;

use Psr\Http\Message\ResponseInterface;
use VCR\Response as VcrResponse;

/**
 * Creates PHP-VCR responses while preserving repeated PSR-7 headers.
 *
 * @visibility namespace
 */
final class VcrResponseFactory
{
    /**
     * Convert a PSR-7 response without flattening repeated header values.
     */
    public function fromPsr7(ResponseInterface $response): VcrResponse
    {
        /** @var array<string, list<string>|string> $headers */
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headerName = (string) $name;
            $headers[$headerName] = count($values) === 1 ? $values[0] : array_values($values);
        }

        return new VcrResponse(
            (string) $response->getStatusCode(),
            $headers,
            (string) $response->getBody(),
        );
    }
}
