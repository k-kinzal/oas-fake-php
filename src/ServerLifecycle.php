<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\ServerStateException;

/**
 * Owns a server's interceptor and registry attachment state.
 *
 * @visibility namespace
 */
final class ServerLifecycle
{
    private ?Interceptor $interceptor = null;

    private ?ServerRegistry $registry = null;

    private ?string $registryKey = null;

    /**
     * Reject configuration changes after the interceptor has started.
     *
     * @throws ServerStateException when the server is running
     */
    public function assertConfigurable(): void
    {
        if ($this->isRunning()) {
            throw ServerStateException::configurationLocked();
        }
    }

    /**
     * Reject registration by a second registry.
     *
     * @throws ServerStateException when another registry owns the server
     */
    public function assertCanRegister(ServerRegistry $registry, string $key): void
    {
        if ($this->registry !== null && ($this->registry !== $registry || $this->registryKey !== $key)) {
            throw ServerStateException::alreadyRegistered();
        }
    }

    /**
     * Attach the server to its owning registry after successful construction.
     *
     * @throws ServerStateException when another registry owns the server
     */
    public function register(ServerRegistry $registry, string $key): void
    {
        $this->assertCanRegister($registry, $key);
        $this->registry = $registry;
        $this->registryKey = $key;
    }

    /**
     * Return the owning registry and key, if registered.
     *
     * @return array{registry: ServerRegistry, key: string}|null
     */
    public function registration(): ?array
    {
        if ($this->registry === null || $this->registryKey === null) {
            return null;
        }

        return [
            'registry' => $this->registry,
            'key' => $this->registryKey,
        ];
    }

    /**
     * Detach from a matching registry and stop the interceptor.
     */
    public function unregister(ServerRegistry $registry, string $key): void
    {
        if ($this->registry !== $registry || $this->registryKey !== $key) {
            return;
        }

        $this->registry = null;
        $this->registryKey = null;
        $this->stopInterceptor();
    }

    /**
     * Replace the active interceptor after it has started successfully.
     */
    public function replaceInterceptor(Interceptor $interceptor): void
    {
        $this->interceptor = $interceptor;
    }

    /**
     * Return the active interceptor, if one exists.
     */
    public function interceptor(): ?Interceptor
    {
        return $this->interceptor;
    }

    /**
     * Report whether the active interceptor is running.
     */
    public function isRunning(): bool
    {
        return $this->interceptor !== null && $this->interceptor->isRunning();
    }

    /**
     * Stop and release the active interceptor.
     */
    public function stopInterceptor(): void
    {
        if ($this->interceptor !== null) {
            $this->interceptor->stop();
            $this->interceptor = null;
        }
    }
}
