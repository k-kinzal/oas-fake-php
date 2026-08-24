<?php

declare(strict_types=1);

namespace OasFake;

use function array_values;
use function count;
use function implode;

use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use VCR\Response as VcrResponse;

/**
 * Creates PHP-VCR responses while preserving repeated PSR-7 headers.
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
        /** @var array<string, string> $constructorHeaders */
        $constructorHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headerName = (string) $name;
            $headers[$headerName] = count($values) === 1 ? $values[0] : array_values($values);
            $constructorHeaders[$headerName] = implode(', ', $values);
        }

        $vcrResponse = new VcrResponse(
            (string) $response->getStatusCode(),
            $constructorHeaders,
            (string) $response->getBody(),
        );

        $headersProperty = new ReflectionProperty(VcrResponse::class, 'headers');
        $headersProperty->setAccessible(true);
        $headersProperty->setValue($vcrResponse, $headers);

        return $vcrResponse;
    }
}
