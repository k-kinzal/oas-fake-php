<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

#[CoversClass(Converter::class)]
final class ConverterTest extends TestCase
{
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new Converter();
    }

    public function testRequestToPsr7ConvertsUrlMethodHeadersAndBody(): void
    {
        $vcrRequest = new VcrRequest(
            'POST',
            'http://example.com/pets',
            ['Content-Type' => 'application/json'],
        );
        $vcrRequest->setBody('{"name":"Rex"}');

        $psr7 = $this->converter->requestToPsr7($vcrRequest);

        self::assertSame('POST', $psr7->getMethod());
        self::assertSame('http://example.com/pets', (string) $psr7->getUri());
        self::assertSame('application/json', $psr7->getHeaderLine('Content-Type'));
        self::assertSame('{"name":"Rex"}', (string) $psr7->getBody());
    }

    public function testRequestToPsr7ParsesQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets?limit=10&offset=20',
        );

        $psr7 = $this->converter->requestToPsr7($vcrRequest);

        self::assertSame(['limit' => '10', 'offset' => '20'], $psr7->getQueryParams());
    }

    public function testRequestToPsr7PreservesRepeatedQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets?tag=friendly&tag=small&name=Buddy+Jr&include',
        );

        $psr7 = $this->converter->requestToPsr7($vcrRequest);

        self::assertSame(
            [
                'tag' => ['friendly', 'small'],
                'name' => 'Buddy Jr',
                'include' => null,
            ],
            $psr7->getQueryParams(),
        );
    }

    public function testRequestToPsr7HandlesNoQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets',
        );

        $psr7 = $this->converter->requestToPsr7($vcrRequest);

        self::assertSame([], $psr7->getQueryParams());
    }

    public function testPsr7ToVcrResponseConvertsStatusHeadersAndBody(): void
    {
        $psrResponse = new Response(
            201,
            ['Content-Type' => 'application/json', 'X-Request-Id' => 'abc123'],
            '{"id":1}',
        );

        $vcrResponse = $this->converter->psr7ToVcrResponse($psrResponse);

        self::assertSame(201, $vcrResponse->getStatusCode());
        self::assertSame('application/json', $vcrResponse->getHeader('Content-Type'));
        self::assertSame('abc123', $vcrResponse->getHeader('X-Request-Id'));
        self::assertSame('{"id":1}', $vcrResponse->getBody());
    }

    public function testPsr7ToVcrResponsePreservesMultipleHeaderValues(): void
    {
        $psrResponse = new Response(
            200,
            [
                'Set-Cookie' => [
                    'session=abc; Path=/; HttpOnly',
                    'theme=dark; Path=/',
                ],
            ],
            'ok',
        );

        $vcrResponse = $this->converter->psr7ToVcrResponse($psrResponse);
        $restored = $this->converter->vcrResponseToPsr7($vcrResponse);

        self::assertSame(
            ['session=abc; Path=/; HttpOnly', 'theme=dark; Path=/'],
            $vcrResponse->getHeaders()['Set-Cookie'],
        );
        self::assertSame(
            ['session=abc; Path=/; HttpOnly', 'theme=dark; Path=/'],
            $restored->getHeader('Set-Cookie'),
        );
    }

    public function testVcrResponseToPsr7ConvertsStatusHeadersAndBody(): void
    {
        // @phpstan-ignore argument.type
        $vcrResponse = new VcrResponse(
            ['code' => 200, 'message' => 'OK'],
            ['Content-Type' => 'application/json'],
            '{"ok":true}',
        );

        $psrResponse = $this->converter->vcrResponseToPsr7($vcrResponse);

        self::assertSame(200, $psrResponse->getStatusCode());
        self::assertSame('application/json', $psrResponse->getHeaderLine('Content-Type'));
        self::assertSame('{"ok":true}', (string) $psrResponse->getBody());
    }

    public function testRoundTripPsr7ToVcrAndBackPreservesData(): void
    {
        $original = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"round":"trip"}',
        );

        $vcrResponse = $this->converter->psr7ToVcrResponse($original);
        $restored = $this->converter->vcrResponseToPsr7($vcrResponse);

        self::assertSame($original->getStatusCode(), $restored->getStatusCode());
        self::assertSame((string) $original->getBody(), (string) $restored->getBody());
        self::assertSame(
            $original->getHeaderLine('Content-Type'),
            $restored->getHeaderLine('Content-Type'),
        );
    }
}
