<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OasFake\Stub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(Stub::class)]
final class StubTest extends TestCase
{
    public function testResponseFactoryCreatesStubWithCorrectStatusBodyAndHeaders(): void
    {
        $stub = Stub::response(201, ['id' => 1], ['X-Custom' => 'value']);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $stub->resolve($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('{"id":1}', (string) $response->getBody());
        self::assertSame('value', $response->getHeaderLine('X-Custom'));
    }

    public function testResponseFactoryDefaultsContentTypeToJson(): void
    {
        $stub = Stub::response(200, ['ok' => true]);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $stub->resolve($request);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseFactoryAcceptsStringBody(): void
    {
        $stub = Stub::response(200, '<xml/>');
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $stub->resolve($request);

        self::assertSame('<xml/>', (string) $response->getBody());
    }

    public function testResponseFactoryDoesNotOverrideExplicitContentType(): void
    {
        $stub = Stub::response(200, 'text', ['Content-Type' => 'text/plain']);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $stub->resolve($request);

        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public function testCallbackFactoryInvokesCallbackWithRequestAndDefault(): void
    {
        $expectedResponse = new Response(418);
        $receivedRequest = null;
        $receivedDefault = null;

        $stub = Stub::callback(static function (
            ServerRequestInterface $request,
            ?ResponseInterface $default,
        ) use ($expectedResponse, &$receivedRequest, &$receivedDefault): ResponseInterface {
            $receivedRequest = $request;
            $receivedDefault = $default;

            return $expectedResponse;
        });

        $request = new ServerRequest('POST', 'http://example.com/test');
        $defaultResponse = new Response(200);

        $response = $stub->resolve($request, $defaultResponse);

        self::assertSame($expectedResponse, $response);
        self::assertSame($request, $receivedRequest);
        self::assertSame($defaultResponse, $receivedDefault);
    }

    public function testStatusFactoryCreatesStubWithJustStatusCode(): void
    {
        $stub = Stub::status(204);
        $request = new ServerRequest('DELETE', 'http://example.com/test');

        $response = $stub->resolve($request);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
    }

    public function testStatusStubUsesDefaultResponseBodyWhenAvailable(): void
    {
        $stub = Stub::status(201);
        $request = new ServerRequest('GET', 'http://example.com/test');
        $defaultResponse = new Response(200, ['Content-Type' => 'application/json'], '{"default":true}');

        $response = $stub->resolve($request, $defaultResponse);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('{"default":true}', (string) $response->getBody());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testResolveWithStaticResponseIgnoresDefault(): void
    {
        $stub = Stub::response(200, ['stubbed' => true]);
        $request = new ServerRequest('GET', 'http://example.com/test');
        $defaultResponse = new Response(500, [], '{"error":"fail"}');

        $response = $stub->resolve($request, $defaultResponse);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"stubbed":true}', (string) $response->getBody());
    }
}
