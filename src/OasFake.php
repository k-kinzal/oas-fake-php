<?php

declare(strict_types=1);

namespace OasFake;

use function is_string;

/**
 * Main facade for starting and stopping fake API servers.
 *
 * Provides static methods to manage Server instances through a shared ServerRegistry.
 */
final class OasFake
{
    private static ?ServerRegistry $registry = null;

    private function __construct()
    {
    }

    /**
     * Start a fake server and return its instance.
     *
     * @template T of Server
     *
     * @param class-string<T>|T $server Server class or instance
     * @param (callable(T): T)|null $configure Optional fluent configuration callback
     *
     * @return T
     */
    public static function start(string|Server $server, ?callable $configure = null): Server
    {
        if (is_string($server)) {
            /** @var T $server */
            $server = new $server();
        }

        if ($configure !== null) {
            $server = $configure($server);
        }

        self::registry()->register($server::class, $server);

        return $server;
    }

    /**
     * Stop all running fake servers and clean up the registry.
     */
    public static function stop(): void
    {
        if (self::$registry !== null) {
            self::$registry->unregisterAll();
        }
    }

    private static function registry(): ServerRegistry
    {
        return self::$registry ??= new ServerRegistry();
    }
}
