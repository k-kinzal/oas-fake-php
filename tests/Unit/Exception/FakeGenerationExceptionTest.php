<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use LogicException;
use OasFake\Exception\FakeGenerationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeGenerationException::class)]
final class FakeGenerationExceptionTest extends TestCase
{
    public function testForOperationPreservesGenerationCause(): void
    {
        $previous = new LogicException('faker failed');
        $exception = FakeGenerationException::forOperation('GET /pets', $previous);

        self::assertStringContainsString('GET /pets', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
