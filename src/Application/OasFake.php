<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;

use function is_string;

use ReflectionException;

use function spl_object_id;

/**
 * Main facade for starting and stopping fake API servers.
 *
 * Provides static methods to manage Server instances through a shared ServerRegistry.
 *
 * @visibility public
 *
 * @example Stopping when no server is active is safe
 *     \OasFake\OasFake::stop();
 *     true // => true
     */
final class OasFake
{
    private static ?ServerRegistry $registry = null;

    /**
     * Start a fake server and return its instance.
     *
     * @template T of Server
     *
     * @param class-string<T>|T $server +mut initializes and registers a supplied server instance
     * @param (callable(T): T)|null $configure Optional fluent configuration callback
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws ReflectionException when a declarative handler cannot be bound
     *
     * @return T
     *
     * @mutation global
     */
    public static function start(string|Server $server, ?callable $configure = null): Server
    {
        $instance = is_string($server) ? new $server() : $server;
        $configured = $configure !== null ? $configure($instance) : $instance;

        (self::$registry ??= new ServerRegistry())->register(
            $configured::class . '#' . spl_object_id($configured),
            $configured,
        );

        return $configured;
    }

    /**
     * Stop one fake server, or all running fake servers when omitted.
     *
     *
     * @mutation global
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
