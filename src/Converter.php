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

final class Converter
{
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

    public function vcrResponseToPsr7(VcrResponse $vcrResponse): ResponseInterface
    {
        return new Response(
            $vcrResponse->getStatusCode(),
            $vcrResponse->getHeaders(),
            $vcrResponse->getBody(),
        );
    }
}
