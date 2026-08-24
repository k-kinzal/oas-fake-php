<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Server;
use OasFake\ServerMiddleware;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(ServerMiddleware::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
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
