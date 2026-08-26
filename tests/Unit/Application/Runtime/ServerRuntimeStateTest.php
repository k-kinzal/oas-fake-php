<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Mode;
use OasFake\Server;
use OasFake\ServerRuntime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\Server
 *
 * @uses \OasFake\Mode
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 * @uses \OasFake\ServerRuntime
 * @uses \OasFake\ServerRuntimeState
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\ServerMiddleware
 * @uses \OasFake\EnvironmentResolver
 */
#[CoversClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerRuntime::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\EnvironmentResolver::class)]
final class ServerRuntimeStateTest extends TestCase
{
    public function testInitializationProvidesDefaultRuntimeConfiguration(): void
    {
        $server = new Server();

        self::assertSame(Mode::FAKE, $server->resolveMode()->value());
        self::assertSame([], $server->fakerOptions());
        self::assertFalse($server->isRunning());
    }
}
