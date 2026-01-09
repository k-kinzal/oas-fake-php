<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Vcr;

use InvalidArgumentException;
use OasFakePHP\Vcr\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mode::class)]
final class ModeTest extends TestCase
{
    public function testFromEnvironmentReturnsReplayWhenNotSet(): void
    {
        putenv('OAS_FAKE_VCR_MODE');

        $mode = Mode::fromEnvironment();

        self::assertSame(Mode::REPLAY, $mode);
    }

    public function testFromEnvironmentReturnsReplayWhenEmpty(): void
    {
        putenv('OAS_FAKE_VCR_MODE=');

        $mode = Mode::fromEnvironment();

        self::assertSame(Mode::REPLAY, $mode);
    }

    #[DataProvider('provideValidModes')]
    public function testFromEnvironmentReturnsCorrectMode(string $envValue, Mode $expected): void
    {
        putenv('OAS_FAKE_VCR_MODE=' . $envValue);

        $mode = Mode::fromEnvironment();

        self::assertSame($expected, $mode);
    }

    #[DataProvider('provideValidModes')]
    public function testFromStringReturnsCorrectMode(string $value, Mode $expected): void
    {
        $mode = Mode::fromString($value);

        self::assertSame($expected, $mode);
    }

    public function testFromStringThrowsOnInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mode "invalid"');

        Mode::fromString('invalid');
    }

    public function testFromStringHandlesWhitespace(): void
    {
        $mode = Mode::fromString('  record  ');

        self::assertSame(Mode::RECORD, $mode);
    }

    public function testFromStringHandlesUppercase(): void
    {
        $mode = Mode::fromString('RECORD');

        self::assertSame(Mode::RECORD, $mode);
    }

    /**
     * @return iterable<string, array{string, Mode}>
     */
    public static function provideValidModes(): iterable
    {
        yield 'record' => ['record', Mode::RECORD];
        yield 'replay' => ['replay', Mode::REPLAY];
        yield 'passthrough' => ['passthrough', Mode::PASSTHROUGH];
    }

    protected function tearDown(): void
    {
        putenv('OAS_FAKE_VCR_MODE');
    }
}
