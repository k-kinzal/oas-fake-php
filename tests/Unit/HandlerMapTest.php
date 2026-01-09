<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Handler;
use OasFake\HandlerMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerMap::class)]
final class HandlerMapTest extends TestCase
{
    public function testForOperationAndFindByOperationId(): void
    {
        $map = new HandlerMap();
        $handler = Handler::status(200);

        $map->forOperation('listPets', $handler);

        $found = $map->find('listPets', '/pets', 'GET');
        self::assertSame($handler, $found);
    }

    public function testForPathAndFindByPathAndMethod(): void
    {
        $map = new HandlerMap();
        $handler = Handler::status(201);

        $map->forPath('/pets', 'POST', $handler);

        $found = $map->find('', '/pets', 'POST');
        self::assertSame($handler, $found);
    }

    public function testFindPrioritizesOperationIdOverPathMethod(): void
    {
        $map = new HandlerMap();
        $opHandler = Handler::status(200);
        $pathHandler = Handler::status(201);

        $map->forOperation('listPets', $opHandler);
        $map->forPath('/pets', 'GET', $pathHandler);

        $found = $map->find('listPets', '/pets', 'GET');
        self::assertSame($opHandler, $found);
    }

    public function testFindFallsBackToPathMethodWhenOperationIdNotFound(): void
    {
        $map = new HandlerMap();
        $pathHandler = Handler::status(200);

        $map->forPath('/pets', 'GET', $pathHandler);

        $found = $map->find('nonExistent', '/pets', 'GET');
        self::assertSame($pathHandler, $found);
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $map = new HandlerMap();

        $found = $map->find('unknown', '/unknown', 'GET');
        self::assertNull($found);
    }

    public function testFindIsCaseInsensitiveForMethod(): void
    {
        $map = new HandlerMap();
        $handler = Handler::status(200);

        $map->forPath('/pets', 'get', $handler);

        $found = $map->find('', '/pets', 'GET');
        self::assertSame($handler, $found);
    }

    public function testClearRemovesAllHandlers(): void
    {
        $map = new HandlerMap();
        $map->forOperation('listPets', Handler::status(200));
        $map->forPath('/pets', 'GET', Handler::status(200));

        $map->clear();

        self::assertNull($map->find('listPets', '/pets', 'GET'));
        self::assertTrue($map->isEmpty());
    }

    public function testIsEmptyReturnsTrueWhenEmpty(): void
    {
        $map = new HandlerMap();

        self::assertTrue($map->isEmpty());
    }

    public function testIsEmptyReturnsFalseWithOperationHandler(): void
    {
        $map = new HandlerMap();
        $map->forOperation('listPets', Handler::status(200));

        self::assertFalse($map->isEmpty());
    }

    public function testIsEmptyReturnsFalseWithPathHandler(): void
    {
        $map = new HandlerMap();
        $map->forPath('/pets', 'GET', Handler::status(200));

        self::assertFalse($map->isEmpty());
    }
}
