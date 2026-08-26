<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\Route
 */
#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    public function testStoresRouteMetadata(): void
    {
        $route = new Route('DELETE', '/pets/{petId}');

        self::assertSame('DELETE', $route->method);
        self::assertSame('/pets/{petId}', $route->path);
    }
}
