<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\PayloadSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
final class PayloadSerializerTest extends TestCase
{
    /**
     * @throws JsonException when the payload cannot be serialized
     */
    public function testSerializesJsonMediaType(): void
    {
        self::assertSame('{"status":"ok"}', PayloadSerializer::serialize(['status' => 'ok'], 'application/json'));
    }

    /**
     * @throws JsonException when the payload cannot be serialized
     */
    public function testSerializesTextMediaType(): void
    {
        self::assertSame('ok', PayloadSerializer::serialize('ok', 'text/plain'));
    }

    /**
     * @throws JsonException when the payload cannot be serialized
     */
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
