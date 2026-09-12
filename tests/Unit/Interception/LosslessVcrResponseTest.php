<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\LosslessVcrResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Response;

/**
 * @covers \OasFake\LosslessVcrResponse
 */
#[CoversClass(LosslessVcrResponse::class)]
final class LosslessVcrResponseTest extends TestCase
{
    public function testGetBodyDistinguishesAZeroBodyFromAnAbsentBody(): void
    {
        self::assertSame('0', (new LosslessVcrResponse('200', [], '0'))->getBody());
        self::assertSame('', (new LosslessVcrResponse('204'))->getBody());
    }

    public function testFromResponsePreservesAZeroBodyAndResponseMetadata(): void
    {
        $original = new Response(['code' => '201', 'message' => 'Created'], ['X-Values' => ['a', 'b']], '0', ['total_time' => 0.5]);
        $response = LosslessVcrResponse::fromResponse($original);

        self::assertSame('0', $response->getBody());
        self::assertSame([
            'status' => ['code' => 201, 'message' => 'Created'],
            'headers' => ['X-Values' => ['a', 'b']],
            'curl_info' => ['total_time' => 0.5],
            'body' => '0',
        ], $response->toArray());
    }

    public function testToArrayRetainsBinaryCassetteEncoding(): void
    {
        $response = new LosslessVcrResponse('200', ['Content-Type' => 'application/octet-stream', 'Content-Transfer-Encoding' => 'binary'], '0');

        self::assertSame('MA==', $response->toArray()['body']);
        self::assertSame('0', LosslessVcrResponse::fromArray($response->toArray())->getBody());
    }

    public function testToArrayPreservesEmptyBodyOmission(): void
    {
        $response = new LosslessVcrResponse('204');

        self::assertSame('', $response->getBody());
        self::assertArrayNotHasKey('body', $response->toArray());
    }
}
