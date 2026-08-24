<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\PayloadCodec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(PayloadCodec::class)]
final class PayloadCodecTest extends TestCase
{
    /**
     * @throws JsonException when the payload cannot be serialized
     */
    public function testEncodeJsonHandlesSupportedValuesAndObjects(): void
    {
        $codec = new PayloadCodec();

        self::assertSame('[1,2]', $codec->encodeJson([1, 2]));
        self::assertSame('null', $codec->encodeJson(new stdClass()));
    }

    /**
     * @throws JsonException when the payload cannot be serialized
     */
    public function testEncodeTextHandlesScalarsAndStructures(): void
    {
        $codec = new PayloadCodec();

        self::assertSame('ok', $codec->encodeText('ok'));
        self::assertSame('{"ok":true}', $codec->encodeText(['ok' => true]));
    }

    public function testNormalizeMediaTypeRemovesParametersAndFoldsCase(): void
    {
        $codec = new PayloadCodec();

        self::assertSame('application/json', $codec->normalizeMediaType('Application/JSON; charset=utf-8'));
    }

    public function testIsJsonMediaTypeRecognizesStructuredJson(): void
    {
        $codec = new PayloadCodec();

        self::assertTrue($codec->isJsonMediaType('application/problem+json'));
        self::assertFalse($codec->isJsonMediaType('text/plain'));
    }
}
