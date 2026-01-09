<?php

declare(strict_types=1);

namespace OasFake;

use Closure;
use GuzzleHttp\Psr7\Response;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Represents a handler for a specific API operation.
 */
final class Handler
{
    /**
     * @param array<string, mixed>|list<mixed>|string|null $body
     * @param array<string, string> $headers
     * @param (Closure(ServerRequestInterface, ?ResponseInterface): ResponseInterface)|null $callback
     */
    private function __construct(
        private ?int $statusCode,
        private array|string|null $body,
        private array $headers,
        private ?Closure $callback,
    ) {
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     */
    public static function response(int $status, array|string $body, array $headers = []): self
    {
        return new self($status, $body, $headers, null);
    }

    /**
     * Create a handler that delegates response generation to a callback.
     *
     * @param callable(ServerRequestInterface, ?ResponseInterface): ResponseInterface $callback
     */
    public static function callback(callable $callback): self
    {
        return new self(null, null, [], Closure::fromCallable($callback));
    }

    /**
     * Create a handler that returns an empty response with the given status code.
     *
     * @param int $status The HTTP status code
     */
    public static function status(int $status): self
    {
        return new self($status, null, [], null);
    }

    /**
     * Resolve the handler into a PSR-7 response.
     *
     * For callback handlers, invokes the callback with the request and an optional default response.
     * For response handlers, returns the configured body or falls back to the default.
     *
     * @param ServerRequestInterface $request The incoming request
     * @param ResponseInterface|null $default An optional faker-generated default response
     *
     * @return ResponseInterface The resolved response
     */
    public function resolve(ServerRequestInterface $request, ?ResponseInterface $default = null): ResponseInterface
    {
        if ($this->callback !== null) {
            return ($this->callback)($request, $default);
        }

        $statusCode = $this->statusCode ?? 200;
        $headers = $this->headers;

        if ($this->body === null) {
            if ($default !== null) {
                return $default->withStatus($statusCode);
            }

            return new Response($statusCode, $headers);
        }

        $body = is_array($this->body) ? json_encode($this->body, JSON_THROW_ON_ERROR) : $this->body;
        $headers['Content-Type'] ??= 'application/json';

        return new Response($statusCode, $headers, $body);
    }
}
