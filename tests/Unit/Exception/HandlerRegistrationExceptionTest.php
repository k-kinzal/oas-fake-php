<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\HandlerRegistrationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[CoversClass(HandlerRegistrationException::class)]
final class HandlerRegistrationExceptionTest extends TestCase
{
    public function testForServerPreservesReflectionCause(): void
    {
        $previous = new ReflectionException('attribute failed');
        $exception = HandlerRegistrationException::forServer(self::class, $previous);

        self::assertStringContainsString(self::class, $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
