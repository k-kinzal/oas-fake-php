<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Http;

use GuzzleHttp\Psr7\Response;
use OasFakePHP\Http\ResponseConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Response as VcrResponse;

#[CoversClass(ResponseConverter::class)]
final class ResponseConverterTest extends TestCase
{
    private ResponseConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ResponseConverter();
    }

    public function testVcrToPsr7ConvertsBasicResponse(): void
    {
        $vcrResponse = new VcrResponse(['code' => 200, 'message' => 'OK'], [], '{"id": 1}');

        $psr7Response = $this->converter->vcrToPsr7($vcrResponse);

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('{"id": 1}', (string) $psr7Response->getBody());
    }

    public function testVcrToPsr7ConvertsHeaders(): void
    {
        $vcrResponse = new VcrResponse(['code' => 200, 'message' => 'OK'], [
            'Content-Type' => 'application/json',
        ], '{}');

        $psr7Response = $this->converter->vcrToPsr7($vcrResponse);

        self::assertSame('application/json', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testPsr7ToVcrConvertsBasicResponse(): void
    {
        $psr7Response = new Response(201, [], '{"created": true}');

        $vcrResponse = $this->converter->psr7ToVcr($psr7Response);

        self::assertSame(201, $vcrResponse->getStatusCode());
        self::assertSame('{"created": true}', $vcrResponse->getBody());
    }

    public function testPsr7ToVcrConvertsHeaders(): void
    {
        $psr7Response = new Response(200, [
            'Content-Type' => 'application/json',
            'X-Custom' => ['value1', 'value2'],
        ], '{}');

        $vcrResponse = $this->converter->psr7ToVcr($psr7Response);

        $headers = $vcrResponse->getHeaders();
        self::assertSame('application/json', $headers['Content-Type']);
        self::assertSame('value1, value2', $headers['X-Custom']);
    }

    public function testArrayToVcrWithArray(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];

        $vcrResponse = $this->converter->arrayToVcr($data, 200);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame('{"id":1,"name":"Test"}', $vcrResponse->getBody());
        $headers = $vcrResponse->getHeaders();
        self::assertSame('application/json', $headers['Content-Type']);
    }

    public function testArrayToVcrWithScalar(): void
    {
        $vcrResponse = $this->converter->arrayToVcr('plain text', 200);

        self::assertSame('plain text', $vcrResponse->getBody());
    }

    public function testArrayToVcrWithCustomHeaders(): void
    {
        $vcrResponse = $this->converter->arrayToVcr(['data' => true], 201, [
            'X-Custom' => 'value',
        ]);

        self::assertSame(201, $vcrResponse->getStatusCode());
        $headers = $vcrResponse->getHeaders();
        self::assertSame('value', $headers['X-Custom']);
    }

    public function testArrayToPsr7WithArray(): void
    {
        $data = ['id' => 1];

        $psr7Response = $this->converter->arrayToPsr7($data, 200);

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('{"id":1}', (string) $psr7Response->getBody());
        self::assertSame('application/json', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testArrayToPsr7WithScalar(): void
    {
        $psr7Response = $this->converter->arrayToPsr7('test', 200);

        self::assertSame('test', (string) $psr7Response->getBody());
    }

    public function testArrayToPsr7WithNull(): void
    {
        $psr7Response = $this->converter->arrayToPsr7(null, 204);

        self::assertSame(204, $psr7Response->getStatusCode());
    }
}
