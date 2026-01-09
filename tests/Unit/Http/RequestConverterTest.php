<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Http;

use GuzzleHttp\Psr7\ServerRequest;
use OasFakePHP\Http\RequestConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;

#[CoversClass(RequestConverter::class)]
final class RequestConverterTest extends TestCase
{
    private RequestConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new RequestConverter();
    }

    public function testVcrToPsr7ConvertsBasicRequest(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://example.com/pets', []);

        $psr7Request = $this->converter->vcrToPsr7($vcrRequest);

        self::assertSame('GET', $psr7Request->getMethod());
        self::assertSame('https://example.com/pets', (string) $psr7Request->getUri());
    }

    public function testVcrToPsr7ConvertsHeaders(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://example.com/pets', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $psr7Request = $this->converter->vcrToPsr7($vcrRequest);

        self::assertSame('application/json', $psr7Request->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $psr7Request->getHeaderLine('Accept'));
    }

    public function testVcrToPsr7ParsesQueryParameters(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://example.com/pets?limit=10&offset=5', []);

        $psr7Request = $this->converter->vcrToPsr7($vcrRequest);

        $queryParams = $psr7Request->getQueryParams();
        self::assertSame('10', $queryParams['limit']);
        self::assertSame('5', $queryParams['offset']);
    }

    public function testVcrToPsr7HandlesNullUrl(): void
    {
        $vcrRequest = new VcrRequest('GET', null, []);

        $psr7Request = $this->converter->vcrToPsr7($vcrRequest);

        self::assertSame('', (string) $psr7Request->getUri());
    }

    public function testVcrToPsr7HandlesNullHeaders(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://example.com', [
            'X-Custom' => null,
        ]);

        $psr7Request = $this->converter->vcrToPsr7($vcrRequest);

        self::assertSame('', $psr7Request->getHeaderLine('X-Custom'));
    }

    public function testPsr7ToVcrConvertsBasicRequest(): void
    {
        $psr7Request = new ServerRequest('POST', 'https://example.com/pets');

        $vcrRequest = $this->converter->psr7ToVcr($psr7Request);

        self::assertSame('POST', $vcrRequest->getMethod());
        self::assertSame('https://example.com/pets', $vcrRequest->getUrl());
    }

    public function testPsr7ToVcrConvertsHeaders(): void
    {
        $psr7Request = new ServerRequest('GET', 'https://example.com/pets', [
            'Content-Type' => 'application/json',
            'Accept' => ['application/json', 'text/plain'],
        ]);

        $vcrRequest = $this->converter->psr7ToVcr($psr7Request);

        $headers = $vcrRequest->getHeaders();
        self::assertSame('application/json', $headers['Content-Type']);
        self::assertSame('application/json, text/plain', $headers['Accept']);
    }
}
