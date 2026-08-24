<?php

declare(strict_types=1);

namespace OasFake\Testing;

use OasFake\Server;
use OasFake\ServerRegistry;
use Override;

/**
 * Records lifecycle calls while preserving the real Server type contract.
 */
final class InspectableServer extends Server
{
    /**
     * Number of interceptor-build requests.
     */
    public int $buildCount = 0;

    /**
     * Number of registry-unregister requests.
     */
    public int $unregisterCount = 0;

    /**
     * Number of direct stop requests.
     */
    public int $stopCount = 0;

    /**
     * Record interceptor construction without activating PHP-VCR.
     */
    #[Override]
    public function buildInterceptor(): void
    {
        ++$this->buildCount;
    }

    /**
     * Return no routes because this spy never dispatches requests.
     *
     * @return list<string>
     */
    #[Override]
    public function serverUrls(): array
    {
        return [];
    }

    /**
     * Record direct stop requests.
     */
    #[Override]
    public function stop(): void
    {
        ++$this->stopCount;
    }

    /**
     * Record registry detach requests.
     */
    #[Override]
    public function unregisterFromRegistry(ServerRegistry $registry, string $key): void
    {
        ++$this->unregisterCount;
        parent::unregisterFromRegistry($registry, $key);
    }
}
