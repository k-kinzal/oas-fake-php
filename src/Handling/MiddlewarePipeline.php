<?php

declare(strict_types=1);

namespace OasFake;

use function array_reverse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Runs the configured PSR-15 middleware chain.
 *
 * @visibility namespace
 */
final class MiddlewarePipeline
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(private array $middleware)
    {
    }

    /**
     * Process a request through the configured middleware and final handler.
     */
    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $chain = array_reduce(
            array_reverse($this->middleware),
            static fn (RequestHandlerInterface $next, MiddlewareInterface $middleware): RequestHandlerInterface => new MiddlewareRequestHandler($middleware, $next),
            $handler,
        );

        return $chain->handle($request);
    }

    /**
     * Process a request/resolved-response pair through the configured middleware.
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->handle($request, new ResolvedResponseRequestHandler($response));
    }
}
