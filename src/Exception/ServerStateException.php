<?php

declare(strict_types=1);

namespace OasFake\Exception;

/**
 * Thrown when a server lifecycle transition violates its ownership contract.
 */
final class ServerStateException extends OasFakeException
{
    /**
     * Report a configuration mutation attempted while the server is running.
     */
    public static function configurationLocked(): self
    {
        return new self('Cannot change server configuration while the server is running. Configure the server before start().');
    }

    /**
     * Report registration by a registry other than the current owner.
     */
    public static function alreadyRegistered(): self
    {
        return new self('Server is already registered in another ServerRegistry. Stop it before registering it again.');
    }
}
