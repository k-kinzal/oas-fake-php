<?php

declare(strict_types=1);

namespace OasFake;

use function is_string;
use function spl_object_id;

/**
 * Main facade for starting and stopping fake API servers.
 *
 * Provides static methods to manage Server instances through a shared ServerRegistry.
 */
abstract class OasFake
{
    private static ?ServerRegistry $registry = null;

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

        (self::$registry ??= new ServerRegistry())->register(
            $server::class . '#' . spl_object_id($server),
            $server,
        );

        return $server;
    }

    /**
     * Stop one fake server, or all running fake servers when omitted.
     */
    public static function stop(?Server $server = null): void
    {
        if (self::$registry === null) {
            return;
        }

        if ($server !== null) {
            self::$registry->unregister($server::class . '#' . spl_object_id($server));

            return;
        }

        self::$registry->unregisterAll();
    }
}
