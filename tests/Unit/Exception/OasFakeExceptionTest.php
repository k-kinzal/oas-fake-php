<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\OasFakeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(OasFakeException::class)]
final class OasFakeExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new OasFakeException('Failure');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Failure', $exception->getMessage());
    }
}
