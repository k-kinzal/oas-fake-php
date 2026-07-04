<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OasFake\MiddlewareRequestHandler;
use OasFake\ResolvedResponseRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(MiddlewareRequestHandler::class)]
final class MiddlewareRequestHandlerTest extends TestCase
{
    public function testHandleProcessesMiddleware(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request)->withHeader('X-Middleware', 'yes');
            }
        };

        $handler = new MiddlewareRequestHandler(
            $middleware,
            new ResolvedResponseRequestHandler(new Response(200)),
        );

        $response = $handler->handle(new ServerRequest('GET', '/pets'));

        self::assertSame('yes', $response->getHeaderLine('X-Middleware'));
    }
}
