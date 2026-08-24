<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OasFake;
use OasFake\Testing\InspectableServer;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OasFake::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrLifecycle::class)]
final class OasFakeTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testStartWithServerInstance(): void
    {
        $server = new InspectableServer();

        $result = OasFake::start($server);

        self::assertSame($server, $result);
        self::assertSame(1, $server->buildCount);
    }

    public function testStartWithConfigureCallback(): void
    {
        $server = new InspectableServer();
        $callbackInvoked = false;

        OasFake::start($server, static function (InspectableServer $configured) use (&$callbackInvoked): InspectableServer {
            $callbackInvoked = true;

            return $configured;
        });

        self::assertTrue($callbackInvoked);
    }

    public function testStopStopsServer(): void
    {
        $server = new InspectableServer();

        OasFake::start($server);
        OasFake::stop();

        self::assertSame(1, $server->unregisterCount);
    }

    public function testStopWhenNotRunningDoesNothing(): void
    {
        OasFake::stop();
        $this->addToAssertionCount(1);
    }

    public function testStartReturnsServerInstance(): void
    {
        $server = new InspectableServer();

        $result = OasFake::start($server);

        self::assertSame($server, $result);
    }

    public function testStopIsIdempotent(): void
    {
        $server = new InspectableServer();

        OasFake::start($server);
        OasFake::stop();
        OasFake::stop();
        OasFake::stop();

        self::assertSame(1, $server->unregisterCount);
    }

    public function testMultipleServersCanBeStarted(): void
    {
        $server1 = new InspectableServer();
        $server2 = new InspectableServer();

        $result1 = OasFake::start($server1);
        $result2 = OasFake::start($server2);

        self::assertSame($server1, $result1);
        self::assertSame($server2, $result2);
        self::assertSame(1, $server1->buildCount);
        self::assertSame(1, $server2->buildCount);
    }

    public function testStopStopsAllServers(): void
    {
        $server1 = new InspectableServer();
        $server2 = new InspectableServer();

        OasFake::start($server1);
        OasFake::start($server2);
        OasFake::stop();

        self::assertSame(1, $server1->unregisterCount);
        self::assertSame(1, $server2->unregisterCount);
    }

    public function testStopCanSelectOneServer(): void
    {
        $first = new InspectableServer();
        $second = new InspectableServer();
        OasFake::start($first);
        OasFake::start($second);

        OasFake::stop($first);

        self::assertSame(1, $first->unregisterCount);
        self::assertSame(0, $second->unregisterCount);
    }

    public function testStartingSameServerClassTwiceKeepsFirstInstanceRegistered(): void
    {
        $server1 = new InspectableServer();
        $server2 = new InspectableServer();

        OasFake::start($server1);
        OasFake::start($server2);

        self::assertSame(0, $server1->unregisterCount);
        self::assertSame(0, $server2->unregisterCount);

        OasFake::stop();

        self::assertSame(1, $server1->unregisterCount);
        self::assertSame(1, $server2->unregisterCount);
    }
}
