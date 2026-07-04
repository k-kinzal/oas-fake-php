<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\OperationNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationNotFoundException::class)]
final class OperationNotFoundExceptionTest extends TestCase
{
    public function testForOperationIdCreatesMessage(): void
    {
        $exception = OperationNotFoundException::forOperationId('listPets');

        self::assertSame('Operation not found: listPets', $exception->getMessage());
    }

    public function testForPathAndMethodCreatesMessage(): void
    {
        $exception = OperationNotFoundException::forPathAndMethod('/pets', 'get');

        self::assertSame('Operation not found for GET /pets', $exception->getMessage());
    }
}
