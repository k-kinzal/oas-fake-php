<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Server;
use OasFake\ServerMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerMiddleware::class)]
final class ServerMiddlewareTest extends TestCase
{
    public function testTraitProvidesEmptyDeclarativeMiddlewareByDefault(): void
    {
        self::assertSame([], (new ServerMiddlewareFixture())->resolveMiddleware());
    }
}

final class ServerMiddlewareFixture extends Server
{
}
