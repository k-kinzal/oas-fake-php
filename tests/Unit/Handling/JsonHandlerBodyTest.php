<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\JsonHandlerBody;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\JsonHandlerBody
 */
#[CoversClass(JsonHandlerBody::class)]
final class JsonHandlerBodyTest extends TestCase
{
    /**
     * @throws JsonException when the fixture cannot be encoded
     */
    public function testEncodeSerializesArrayBodiesAsJson(): void
    {
        self::assertSame('{"ready":true}', (new JsonHandlerBody(['ready' => true]))->encode());
    }
}
