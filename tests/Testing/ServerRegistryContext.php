<?php

declare(strict_types=1);

namespace OasFake\Testing;

use OasFake\ServerRegistry;

/**
 * Owns a registry for one test scenario and guarantees lifecycle cleanup.
 */
final class ServerRegistryContext
{
    private ServerRegistry $registry;

    /**
     * Create a fresh registry.
     */
    public function __construct()
    {
        $this->registry = new ServerRegistry();
    }

    /**
     * Deactivate VCR and detach every server when the scenario ends.
     */
    public function __destruct()
    {
        $this->registry->unregisterAll();
    }

    /**
     * Return the owned registry.
     */
    public function registry(): ServerRegistry
    {
        return $this->registry;
    }
}
