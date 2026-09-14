<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\ResponseSnapshotFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\ResponseSnapshotFactory
 */
#[CoversClass(ResponseSnapshotFactory::class)]
final class ResponseSnapshotFactoryTest extends TestCase
{
    public function testCreatePreservesStatusAndBodyAndCombinesRepeatedHeaders(): void
    {
        $response = new Response(202, ['X-Tags' => ['one', 'two']], "body\0");

        self::assertSame([
            'statusCode' => 202,
            'headers' => ['X-Tags' => 'one, two'],
            'rawBody' => "body\0",
        ], (new ResponseSnapshotFactory())->create($response));
        self::assertSame(['one', 'two'], $response->getHeader('X-Tags'));
    }

    public function testCreatePreservesEmptyHeadersAndBody(): void
    {
        self::assertSame([
            'statusCode' => 204,
            'headers' => [],
            'rawBody' => '',
        ], (new ResponseSnapshotFactory())->create(new Response(204)));
    }
}
