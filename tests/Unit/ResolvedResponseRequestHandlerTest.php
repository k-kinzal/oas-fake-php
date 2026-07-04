<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OasFake\ResolvedResponseRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolvedResponseRequestHandler::class)]
final class ResolvedResponseRequestHandlerTest extends TestCase
{
    public function testHandleReturnsResolvedResponse(): void
    {
        $response = new Response(200);
        $handler = new ResolvedResponseRequestHandler($response);

        self::assertSame($response, $handler->handle(new ServerRequest('GET', '/pets')));
    }
}
