<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\SchemaNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaNotFoundException::class)]
final class SchemaNotFoundExceptionTest extends TestCase
{
    public function testForPathCreatesMessage(): void
    {
        $exception = SchemaNotFoundException::forPath('/tmp/openapi.yaml');

        self::assertSame('OpenAPI schema file not found: /tmp/openapi.yaml', $exception->getMessage());
    }
}
