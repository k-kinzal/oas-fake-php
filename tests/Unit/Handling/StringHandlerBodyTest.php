<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\StringHandlerBody;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\StringHandlerBody
 */
#[CoversClass(StringHandlerBody::class)]
final class StringHandlerBodyTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testEncodeReturnsStringBodiesUnchanged(): void
    {
        self::assertSame('ready', (new StringHandlerBody('ready'))->encode());
    }
}
