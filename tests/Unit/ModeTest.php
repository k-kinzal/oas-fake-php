<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use InvalidArgumentException;
use OasFake\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mode::class)]
final class ModeTest extends TestCase
{
    public function testAllCasesHaveCorrectValues(): void
    {
        self::assertSame('fake', Mode::FAKE->value);
        self::assertSame('record', Mode::RECORD->value);
        self::assertSame('replay', Mode::REPLAY->value);
    }

    public function testFromStringFake(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('fake'));
    }

    public function testFromStringRecord(): void
    {
        self::assertSame(Mode::RECORD, Mode::fromString('record'));
    }

    public function testFromStringReplay(): void
    {
        self::assertSame(Mode::REPLAY, Mode::fromString('replay'));
    }

    public function testFromStringIsCaseInsensitive(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('FAKE'));
        self::assertSame(Mode::RECORD, Mode::fromString('Record'));
        self::assertSame(Mode::REPLAY, Mode::fromString('REPLAY'));
    }

    public function testFromStringTrimsWhitespace(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('  fake  '));
    }

    public function testFromStringInvalidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mode "invalid"');

        Mode::fromString('invalid');
    }

    public function testFromEnvironmentDefaultsToFake(): void
    {
        putenv('OAS_FAKE_MODE');

        self::assertSame(Mode::FAKE, Mode::fromEnvironment());
    }

    public function testFromEnvironmentReadsEnvVar(): void
    {
        putenv('OAS_FAKE_MODE=record');

        try {
            self::assertSame(Mode::RECORD, Mode::fromEnvironment());
        } finally {
            putenv('OAS_FAKE_MODE');
        }
    }

    public function testFromEnvironmentWithEmptyStringDefaultsToFake(): void
    {
        putenv('OAS_FAKE_MODE=');

        try {
            self::assertSame(Mode::FAKE, Mode::fromEnvironment());
        } finally {
            putenv('OAS_FAKE_MODE');
        }
    }
}
