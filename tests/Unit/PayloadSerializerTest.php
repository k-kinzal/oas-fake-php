<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\PayloadSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayloadSerializer::class)]
final class PayloadSerializerTest extends TestCase
{
    public function testSerializesJsonMediaType(): void
    {
        self::assertSame('{"status":"ok"}', PayloadSerializer::serialize(['status' => 'ok'], 'application/json'));
    }

    public function testSerializesTextMediaType(): void
    {
        self::assertSame('ok', PayloadSerializer::serialize('ok', 'text/plain'));
    }

    public function testSerializesFormUrlEncodedMediaType(): void
    {
        self::assertSame('status=ok', PayloadSerializer::serialize(['status' => 'ok'], 'application/x-www-form-urlencoded'));
    }

    public function testPreferredMediaTypePrefersJsonWhenAvailable(): void
    {
        self::assertSame('application/json', PayloadSerializer::preferredMediaType(['text/plain', 'application/json']));
    }

    public function testIsJsonMediaTypeMatchesStructuredJson(): void
    {
        self::assertTrue(PayloadSerializer::isJsonMediaType('application/problem+json'));
        self::assertFalse(PayloadSerializer::isJsonMediaType('text/plain'));
    }
}
