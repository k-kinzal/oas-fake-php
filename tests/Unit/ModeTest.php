<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Exception\InvalidModeException;
use OasFake\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(InvalidModeException::class)]
final class ModeTest extends TestCase
{
    public function testNormalizesModeValue(): void
    {
        self::assertSame(Mode::RECORD, (new Mode(' Record '))->value());
    }

    public function testAllCasesHaveCorrectValues(): void
    {
        self::assertSame('fake', Mode::FAKE);
        self::assertSame('record', Mode::RECORD);
        self::assertSame('replay', Mode::REPLAY);
    }

    public function testFromStringFake(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('fake')->value());
    }

    public function testFromStringRecord(): void
    {
        self::assertSame(Mode::RECORD, Mode::fromString('record')->value());
    }

    public function testFromStringReplay(): void
    {
        self::assertSame(Mode::REPLAY, Mode::fromString('replay')->value());
    }

    public function testFromStringIsCaseInsensitive(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('FAKE')->value());
        self::assertSame(Mode::RECORD, Mode::fromString('Record')->value());
        self::assertSame(Mode::REPLAY, Mode::fromString('REPLAY')->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('  fake  ')->value());
    }

    public function testFromAcceptsModeInstance(): void
    {
        $mode = Mode::fromString('record');

        self::assertSame($mode, Mode::from($mode));
    }

    public function testValueReturnsCanonicalString(): void
    {
        self::assertSame(Mode::REPLAY, Mode::fromString('REPLAY')->value());
    }

    public function testIsRecord(): void
    {
        self::assertTrue(Mode::fromString('record')->isRecord());
        self::assertFalse(Mode::fromString('replay')->isRecord());
    }

    public function testIsReplay(): void
    {
        self::assertTrue(Mode::fromString('replay')->isReplay());
        self::assertFalse(Mode::fromString('record')->isReplay());
    }

    public function testFromStringInvalidThrowsException(): void
    {
        $this->expectException(InvalidModeException::class);
        $this->expectExceptionMessage('Invalid mode "invalid"');

        Mode::fromString('invalid');
    }

    public function testFromEnvironmentDefaultsToFake(): void
    {
        putenv('OAS_FAKE_MODE');

        self::assertSame(Mode::FAKE, Mode::fromEnvironment()->value());
    }

    public function testFromEnvironmentReadsEnvVar(): void
    {
        putenv('OAS_FAKE_MODE=record');

        try {
            self::assertSame(Mode::RECORD, Mode::fromEnvironment()->value());
        } finally {
            putenv('OAS_FAKE_MODE');
        }
    }

    public function testFromEnvironmentWithEmptyStringDefaultsToFake(): void
    {
        putenv('OAS_FAKE_MODE=');

        try {
            self::assertSame(Mode::FAKE, Mode::fromEnvironment()->value());
        } finally {
            putenv('OAS_FAKE_MODE');
        }
    }
}
