<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OasFake\MiddlewarePipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(MiddlewarePipeline::class)]
final class MiddlewarePipelineTest extends TestCase
{
    public function testProcessReturnsOriginalResponseWithoutMiddleware(): void
    {
        $request = new ServerRequest('GET', '/pets');
        $response = new Response(200);

        self::assertSame($response, (new MiddlewarePipeline([]))->process($request, $response));
    }

    public function testProcessRunsMiddlewareInConfiguredOrder(): void
    {
        $first = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', $response->getHeaderLine('X-Order') . 'first');
            }
        };
        $second = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', $response->getHeaderLine('X-Order') . 'second');
            }
        };

        $request = new ServerRequest('GET', '/pets');
        $response = (new MiddlewarePipeline([$first, $second]))->process($request, new Response(200));

        self::assertSame('secondfirst', $response->getHeaderLine('X-Order'));
    }

    public function testHandleChainProvidesResolvedResponseToMiddleware(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request)->withHeader('X-Handled', 'yes');
            }
        };

        $request = new ServerRequest('GET', '/pets');
        $response = (new MiddlewarePipeline([$middleware]))->process($request, new Response(200));

        self::assertSame('yes', $response->getHeaderLine('X-Handled'));
    }
}
