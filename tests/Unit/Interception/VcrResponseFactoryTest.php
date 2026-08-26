<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\VcrResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\VcrResponseFactory
 */
#[CoversClass(VcrResponseFactory::class)]
final class VcrResponseFactoryTest extends TestCase
{
    public function testFromPsr7PreservesRepeatedHeaders(): void
    {
        $response = (new VcrResponseFactory())->fromPsr7(new Response(202, ['Set-Cookie' => ['a=1', 'b=2']], 'ok'));

        self::assertSame(202, $response->getStatusCode());
        self::assertSame(['a=1', 'b=2'], $response->getHeaders()['Set-Cookie']);
        self::assertSame('ok', $response->getBody());
    }

    public function testFromPsr7FlattensASingleHeaderValue(): void
    {
        $response = (new VcrResponseFactory())->fromPsr7(new Response(204, ['X-Request-Id' => ['request-1']]));

        self::assertSame('request-1', $response->getHeaders()['X-Request-Id']);
    }
}
