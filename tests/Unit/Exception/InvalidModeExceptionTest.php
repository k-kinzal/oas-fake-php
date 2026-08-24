<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\InvalidModeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidModeException::class)]
final class InvalidModeExceptionTest extends TestCase
{
    public function testForValueDescribesSupportedModes(): void
    {
        $exception = InvalidModeException::forValue('invalid', ['fake', 'record', 'replay']);

        self::assertSame('Invalid mode "invalid". Valid modes are: fake, record, replay', $exception->getMessage());
    }
}
