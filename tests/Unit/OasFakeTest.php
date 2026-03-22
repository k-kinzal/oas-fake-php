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
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testStartWithServerInstance(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('buildInterceptor');

        $result = OasFake::start($server);

        self::assertSame($server, $result);
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
        $server->expects(self::once())->method('buildInterceptor');
        $server->expects(self::once())->method('stop');

        OasFake::start($server);
        OasFake::stop();
    }

    public function testStopWhenNotRunningDoesNothing(): void
    {
        // Should not throw
        OasFake::stop();
        $this->addToAssertionCount(1);
    }

    public function testStartReturnsServerInstance(): void
    {
        $server = $this->createMock(Server::class);

        $result = OasFake::start($server);

        self::assertSame($server, $result);
    }

    public function testStopIsIdempotent(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('stop');

        OasFake::start($server);
        OasFake::stop();
        OasFake::stop(); // Second call should be safe
    }

    public function testMultipleServersCanBeStarted(): void
    {
        $server1 = $this->createMock(Server::class);
        $server1->expects(self::once())->method('buildInterceptor');
        $server2 = $this->createMock(Server::class);
        $server2->expects(self::once())->method('buildInterceptor');

        $result1 = OasFake::start($server1);
        $result2 = OasFake::start($server2);

        self::assertSame($server1, $result1);
        self::assertSame($server2, $result2);
    }

    public function testStopStopsAllServers(): void
    {
        $server1 = $this->createMock(Server::class);
        $server1->expects(self::once())->method('stop');
        $server2 = $this->createMock(Server::class);
        $server2->expects(self::once())->method('stop');

        OasFake::start($server1);
        OasFake::start($server2);
        OasFake::stop();
    }
}
