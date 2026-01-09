<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

/**
 * Converts between PHP-VCR and PSR-7 HTTP message formats.
 */
final class Converter
{
    /**
     * Convert a PHP-VCR request to a PSR-7 server request.
     *
     * @param VcrRequest $vcrRequest The PHP-VCR request to convert
     *
     * @return ServerRequestInterface The equivalent PSR-7 server request
     */
    public function requestToPsr7(VcrRequest $vcrRequest): ServerRequestInterface
    {
        $url = $vcrRequest->getUrl() ?? '';
        $uri = new Uri($url);
        $method = $vcrRequest->getMethod();
        $body = $vcrRequest->getBody();

        /** @var array<string, string|string[]> $headers */
        $headers = [];
        foreach ($vcrRequest->getHeaders() as $name => $value) {
            $headers[(string) $name] = $value ?? '';
        }

        $request = new ServerRequest($method, $uri, $headers, $body);

        $queryString = $uri->getQuery();
        if ($queryString !== '') {
            parse_str($queryString, $queryParams);
            /** @var array<string, array<string>|string> $queryParams */
            $request = $request->withQueryParams($queryParams);
        }

        return $request;
    }

    /**
     * Convert a PSR-7 response to a PHP-VCR response.
     *
     * @param ResponseInterface $psrResponse The PSR-7 response to convert
     *
     * @return VcrResponse The equivalent PHP-VCR response
     */
    public function psr7ToVcrResponse(ResponseInterface $psrResponse): VcrResponse
    {
        $statusCode = $psrResponse->getStatusCode();

        /** @var array<string, string> $flatHeaders */
        $flatHeaders = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $flatHeaders[(string) $name] = implode(', ', $values);
        }

        $body = (string) $psrResponse->getBody();

        // @phpstan-ignore argument.type
        return new VcrResponse(['code' => $statusCode, 'message' => ''], $flatHeaders, $body);
    }

    /**
     * Convert a PHP-VCR response to a PSR-7 response.
     *
     * @param VcrResponse $vcrResponse The PHP-VCR response to convert
     *
     * @return ResponseInterface The equivalent PSR-7 response
     */
    public function vcrResponseToPsr7(VcrResponse $vcrResponse): ResponseInterface
    {
        return new Response(
            $vcrResponse->getStatusCode(),
            $vcrResponse->getHeaders(),
            $vcrResponse->getBody(),
        );
    }
}
