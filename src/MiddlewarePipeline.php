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
        if ($this->middleware === []) {
            return $handler->handle($request);
        }

        $chain = $handler;
        foreach (array_reverse($this->middleware) as $middleware) {
            $chain = new MiddlewareRequestHandler($middleware, $chain);
        }

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
