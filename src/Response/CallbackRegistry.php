<?php

declare(strict_types=1);

namespace OasFakePHP\Response;

use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function strtoupper;

final class CallbackRegistry
{
    /** @var array<string, callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface> */
    private array $operationCallbacks = [];

    /** @var array<string, callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface> */
    private array $pathCallbacks = [];

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public function register(string $operationId, callable $callback): void
    {
        $this->operationCallbacks[$operationId] = $callback;
    }

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public function registerForPath(string $path, string $method, callable $callback): void
    {
        $key = $this->buildPathKey($path, $method);
        $this->pathCallbacks[$key] = $callback;
    }

    public function has(OperationAddress $operation): bool
    {
        $pathKey = $this->buildPathKey($operation->path(), $operation->method());

        return isset($this->pathCallbacks[$pathKey]);
    }

    public function hasForOperationId(string $operationId): bool
    {
        return isset($this->operationCallbacks[$operationId]);
    }

    public function execute(
        OperationAddress $operation,
        ServerRequestInterface $request,
        ?ResponseInterface $defaultResponse,
        ?string $operationId = null,
    ): ResponseInterface {
        // Try operation ID callback first
        if ($operationId !== null && isset($this->operationCallbacks[$operationId])) {
            return ($this->operationCallbacks[$operationId])($request, $defaultResponse);
        }

        // Fall back to path callback
        $pathKey = $this->buildPathKey($operation->path(), $operation->method());
        if (isset($this->pathCallbacks[$pathKey])) {
            return ($this->pathCallbacks[$pathKey])($request, $defaultResponse);
        }

        // If no callback and no default response, this is an error
        if ($defaultResponse === null) {
            throw new RuntimeException(
                sprintf('No callback registered for %s %s and no default response provided', $operation->method(), $operation->path()),
            );
        }

        return $defaultResponse;
    }

    public function clear(): void
    {
        $this->operationCallbacks = [];
        $this->pathCallbacks = [];
    }

    private function buildPathKey(string $path, string $method): string
    {
        return strtoupper($method) . ':' . $path;
    }
}
