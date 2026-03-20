<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Stub;
use OasFake\StubMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StubMap::class)]
final class StubMapTest extends TestCase
{
    public function testForOperationAndFindByOperationId(): void
    {
        $map = new StubMap();
        $stub = Stub::status(200);

        $map->forOperation('listPets', $stub);

        $found = $map->find('listPets', '/pets', 'GET');
        self::assertSame($stub, $found);
    }

    public function testForPathAndFindByPathAndMethod(): void
    {
        $map = new StubMap();
        $stub = Stub::status(201);

        $map->forPath('/pets', 'POST', $stub);

        $found = $map->find('', '/pets', 'POST');
        self::assertSame($stub, $found);
    }

    public function testFindPrioritizesOperationIdOverPathMethod(): void
    {
        $map = new StubMap();
        $opStub = Stub::status(200);
        $pathStub = Stub::status(201);

        $map->forOperation('listPets', $opStub);
        $map->forPath('/pets', 'GET', $pathStub);

        $found = $map->find('listPets', '/pets', 'GET');
        self::assertSame($opStub, $found);
    }

    public function testFindFallsBackToPathMethodWhenOperationIdNotFound(): void
    {
        $map = new StubMap();
        $pathStub = Stub::status(200);

        $map->forPath('/pets', 'GET', $pathStub);

        $found = $map->find('nonExistent', '/pets', 'GET');
        self::assertSame($pathStub, $found);
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $map = new StubMap();

        $found = $map->find('unknown', '/unknown', 'GET');
        self::assertNull($found);
    }

    public function testFindIsCaseInsensitiveForMethod(): void
    {
        $map = new StubMap();
        $stub = Stub::status(200);

        $map->forPath('/pets', 'get', $stub);

        $found = $map->find('', '/pets', 'GET');
        self::assertSame($stub, $found);
    }

    public function testClearRemovesAllStubs(): void
    {
        $map = new StubMap();
        $map->forOperation('listPets', Stub::status(200));
        $map->forPath('/pets', 'GET', Stub::status(200));

        $map->clear();

        self::assertNull($map->find('listPets', '/pets', 'GET'));
        self::assertTrue($map->isEmpty());
    }

    public function testIsEmptyReturnsTrueWhenEmpty(): void
    {
        $map = new StubMap();

        self::assertTrue($map->isEmpty());
    }

    public function testIsEmptyReturnsFalseWithOperationStub(): void
    {
        $map = new StubMap();
        $map->forOperation('listPets', Stub::status(200));

        self::assertFalse($map->isEmpty());
    }

    public function testIsEmptyReturnsFalseWithPathStub(): void
    {
        $map = new StubMap();
        $map->forPath('/pets', 'GET', Stub::status(200));

        self::assertFalse($map->isEmpty());
    }
}
