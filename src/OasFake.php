<?php

declare(strict_types=1);

namespace OasFake;

use function is_string;

final class OasFake
{
    private static ?Server $current = null;

    private function __construct()
    {
    }

    /**
     * Start a fake server.
     *
     * @param class-string<Server>|Server $server Server class or instance
     * @param (callable(Server): Server)|null $configure Optional fluent configuration callback
     */
    public static function start(string|Server $server, ?callable $configure = null): void
    {
        if (is_string($server)) {
            $server = new $server();
        }

        if ($configure !== null) {
            $server = $configure($server);
        }

        self::$current = $server;
        $server->start();
    }

    public static function stop(): void
    {
        if (self::$current !== null) {
            self::$current->stop();
            self::$current = null;
        }
    }

    public static function isRunning(): bool
    {
        return self::$current !== null && self::$current->isRunning();
    }

    public static function current(): ?Server
    {
        return self::$current;
    }

    public static function reset(): void
    {
        self::stop();
    }
}
