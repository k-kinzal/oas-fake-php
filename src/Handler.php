<?php

declare(strict_types=1);

namespace OasFake;

use Closure;
use GuzzleHttp\Psr7\Response;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use OasFake\Exception\HandlerResolutionException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Represents a handler for a specific API operation.
 *
 * @visibility public
 *
 * @example Creating a fixed JSON response
 *     $handler = \OasFake\Handler::response(201, ['id' => 1]);
 *     $response = $handler->resolve(new \GuzzleHttp\Psr7\ServerRequest('POST', '/pets'));
 *     $response->getStatusCode() // => 201
 *     (string) $response->getBody() // => '{"id":1}'
 */
final class Handler
{
    /**
     * Create a handler from one total response strategy.
     *
     * @param Closure(ServerRequestInterface, ?ResponseInterface): ResponseInterface $resolver
     */
    public function __construct(private Closure $resolver)
    {
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     * @param array<string, string> $headers
     *
     * @throws HandlerResolutionException when a fixed array body cannot be encoded
     */
    public static function response(int $status, array|string $body, array $headers = []): self
    {
        try {
            $serializedBody = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
        } catch (JsonException $exception) {
            throw HandlerResolutionException::forBody($status, $exception);
        }
        $headers['Content-Type'] ??= 'application/json';

        return new self(static function () use ($status, $serializedBody, $headers): ResponseInterface {
            return new Response($status, $headers, $serializedBody);
        });
    }

    /**
     * Create a handler that delegates response generation to a callback.
     *
     * @param callable(ServerRequestInterface, ?ResponseInterface): ResponseInterface $callback
     */
    public static function callback(callable $callback): self
    {
        return new self(Closure::fromCallable($callback));
    }

    /**
     * Create a handler that returns an empty response with the given status code.
     *
     * @param int $status The HTTP status code
     */
    public static function status(int $status): self
    {
        return new self(static fn (): ResponseInterface => new Response($status));
    }

    /**
     * Resolve the handler into a PSR-7 response.
     *
     * For callback handlers, invokes the callback with the request and an optional default response.
     * For response handlers, returns the configured body.
     * For status handlers, returns an empty response with the configured status.
     *
     * @param ServerRequestInterface $request The incoming request
     * @param ResponseInterface|null $default An optional faker-generated default response
     *
     * @return ResponseInterface The resolved response
     */
    public function resolve(ServerRequestInterface $request, ?ResponseInterface $default = null): ResponseInterface
    {
        return ($this->resolver)($request, $default);
    }
}
