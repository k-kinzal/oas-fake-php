<?php

declare(strict_types=1);

namespace OasFake;

final class StubMap
{
    /** @var array<string, Stub> keyed by operationId */
    private array $byOperationId = [];

    /** @var array<string, Stub> keyed by "METHOD:/path" */
    private array $byPathMethod = [];

    public function forOperation(string $operationId, Stub $stub): void
    {
        $this->byOperationId[$operationId] = $stub;
    }

    public function forPath(string $path, string $method, Stub $stub): void
    {
        $key = strtoupper($method) . ':' . $path;
        $this->byPathMethod[$key] = $stub;
    }

    public function find(string $operationId, string $path, string $method): ?Stub
    {
        // Priority: operationId first, then path+method
        if ($operationId !== '' && isset($this->byOperationId[$operationId])) {
            return $this->byOperationId[$operationId];
        }

        $key = strtoupper($method) . ':' . $path;

        return $this->byPathMethod[$key] ?? null;
    }

    public function clear(): void
    {
        $this->byOperationId = [];
        $this->byPathMethod = [];
    }

    public function isEmpty(): bool
    {
        return $this->byOperationId === [] && $this->byPathMethod === [];
    }
}
