<?php

declare(strict_types=1);

namespace OasFakePHP\Http;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use VCR\Request as VCRRequest;

final class RequestConverter
{
    public function vcrToPsr7(VCRRequest $vcrRequest): ServerRequestInterface
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

        // Parse query parameters from URI
        $queryString = $uri->getQuery();
        if ($queryString !== '') {
            parse_str($queryString, $queryParams);
            /** @var array<string, array<string>|string> $queryParams */
            $request = $request->withQueryParams($queryParams);
        }

        return $request;
    }

    public function psr7ToVcr(ServerRequestInterface $psrRequest): VCRRequest
    {
        $uri = (string) $psrRequest->getUri();
        $method = $psrRequest->getMethod();

        /** @var array<string, string|null> $flatHeaders */
        $flatHeaders = [];
        foreach ($psrRequest->getHeaders() as $name => $values) {
            $flatHeaders[(string) $name] = implode(', ', $values);
        }

        return new VCRRequest($method, $uri, $flatHeaders);
    }
}
