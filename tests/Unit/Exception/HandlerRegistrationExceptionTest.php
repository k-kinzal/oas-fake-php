<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\HandlerRegistrationException;
use OasFake\Testing\ReflectionFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerRegistrationException::class)]
final class HandlerRegistrationExceptionTest extends TestCase
{
    public function testForServerPreservesReflectionCause(): void
    {
        $previous = ReflectionFailure::attribute();
        $exception = HandlerRegistrationException::forServer(self::class, $previous);

        self::assertStringContainsString(self::class, $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
