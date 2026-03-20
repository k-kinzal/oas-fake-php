<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OasFake;
use OasFake\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OasFake::class)]
final class OasFakeTest extends TestCase
{
    protected function setUp(): void
    {
        OasFake::reset();
    }

    protected function tearDown(): void
    {
        OasFake::reset();
    }

    public function testStartWithServerInstance(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');

        OasFake::start($server);
    }

    public function testStartWithConfigureCallback(): void
    {
        $server = new Server();
        $callbackInvoked = false;

        // The configure callback receives the server and should return it
        // start() will throw because no schema, but the callback should be invoked first
        try {
            OasFake::start($server, static function (Server $s) use (&$callbackInvoked): Server {
                $callbackInvoked = true;

                return $s->withSchema('/nonexistent/schema.yaml');
            });
        } catch (\OasFake\Exception\SchemaNotFoundException) {
            // Expected - no schema file
        }

        self::assertTrue($callbackInvoked);
    }

    public function testStopStopsServer(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');
        $server->expects(self::once())->method('stop');

        OasFake::start($server);
        OasFake::stop();
    }

    public function testStopWhenNotRunningDoesNothing(): void
    {
        // Should not throw
        OasFake::stop();
        self::assertFalse(OasFake::isRunning());
    }

    public function testIsRunningReturnsFalseByDefault(): void
    {
        self::assertFalse(OasFake::isRunning());
    }

    public function testIsRunningReturnsTrueWhenServerIsRunning(): void
    {
        $server = $this->createMock(Server::class);
        $server->method('isRunning')->willReturn(true);
        $server->expects(self::once())->method('start');

        OasFake::start($server);

        self::assertTrue(OasFake::isRunning());
    }

    public function testCurrentReturnsNullByDefault(): void
    {
        self::assertNull(OasFake::current());
    }

    public function testCurrentReturnsRunningServer(): void
    {
        $server = $this->createMock(Server::class);
        OasFake::start($server);

        self::assertSame($server, OasFake::current());
    }

    public function testResetStopsAndClears(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('stop');

        OasFake::start($server);
        OasFake::reset();

        self::assertNull(OasFake::current());
        self::assertFalse(OasFake::isRunning());
    }

    public function testCurrentReturnsNullAfterStop(): void
    {
        $server = $this->createMock(Server::class);
        OasFake::start($server);
        OasFake::stop();

        self::assertNull(OasFake::current());
    }
}
