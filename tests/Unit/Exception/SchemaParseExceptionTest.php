<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use LogicException;
use OasFake\Exception\SchemaParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaParseException::class)]
final class SchemaParseExceptionTest extends TestCase
{
    public function testForSourcePreservesParserCause(): void
    {
        $previous = new LogicException('invalid document');
        $exception = SchemaParseException::forSource('inline YAML', $previous);

        self::assertStringContainsString('inline YAML', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
