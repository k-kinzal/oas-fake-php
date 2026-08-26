<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use OasFake\Handler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \OasFake\Handler
 *
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\StringHandlerBody
 */
#[CoversClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\StringHandlerBody::class)]
final class HandlerTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testResponseFactoryCreatesHandlerWithCorrectStatusBodyAndHeaders(): void
    {
        $handler = Handler::response(201, ['id' => 1], ['X-Custom' => 'value']);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $handler->resolve($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('{"id":1}', (string) $response->getBody());
        self::assertSame('value', $response->getHeaderLine('X-Custom'));
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testResponseFactoryDefaultsContentTypeToJson(): void
    {
        $handler = Handler::response(200, ['ok' => true]);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $handler->resolve($request);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testResponseFactoryAcceptsStringBody(): void
    {
        $handler = Handler::response(200, '<xml/>');
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $handler->resolve($request);

        self::assertSame('<xml/>', (string) $response->getBody());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testResponseFactoryDoesNotOverrideExplicitContentType(): void
    {
        $handler = Handler::response(200, 'text', ['Content-Type' => 'text/plain']);
        $request = new ServerRequest('GET', 'http://example.com/test');

        $response = $handler->resolve($request);

        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testCallbackFactoryInvokesCallbackWithRequestAndDefault(): void
    {
        $expectedResponse = new Response(418);
        $receivedRequest = null;
        $receivedDefault = null;

        $handler = Handler::callback(static function (
            ServerRequestInterface $request,
            ?ResponseInterface $default,
        ) use ($expectedResponse, &$receivedRequest, &$receivedDefault): ResponseInterface {
            $receivedRequest = $request;
            $receivedDefault = $default;

            return $expectedResponse;
        });

        $request = new ServerRequest('POST', 'http://example.com/test');
        $defaultResponse = new Response(200);

        $response = $handler->resolve($request, $defaultResponse);

        self::assertSame($expectedResponse, $response);
        self::assertSame($request, $receivedRequest);
        self::assertSame($defaultResponse, $receivedDefault);
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testStatusFactoryCreatesHandlerWithJustStatusCode(): void
    {
        $handler = Handler::status(204);
        $request = new ServerRequest('DELETE', 'http://example.com/test');

        $response = $handler->resolve($request);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testStatusHandlerIgnoresDefaultResponseBody(): void
    {
        $handler = Handler::status(201);
        $request = new ServerRequest('GET', 'http://example.com/test');
        $defaultResponse = new Response(200, ['Content-Type' => 'application/json'], '{"default":true}');

        $response = $handler->resolve($request, $defaultResponse);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame('', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testResolveWithStaticResponseIgnoresDefault(): void
    {
        $handler = Handler::response(200, ['stubbed' => true]);
        $request = new ServerRequest('GET', 'http://example.com/test');
        $defaultResponse = new Response(500, [], '{"error":"fail"}');

        $response = $handler->resolve($request, $defaultResponse);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"stubbed":true}', (string) $response->getBody());
    }
}
