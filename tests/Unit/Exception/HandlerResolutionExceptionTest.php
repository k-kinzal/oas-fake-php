<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use JsonException;
use OasFake\Exception\HandlerResolutionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerResolutionException::class)]
final class HandlerResolutionExceptionTest extends TestCase
{
    public function testForBodyPreservesEncodingCause(): void
    {
        $previous = new JsonException('encoding failed');
        $exception = HandlerResolutionException::forBody(422, $previous);

        self::assertStringContainsString('422', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
