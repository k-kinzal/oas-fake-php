<?php

declare(strict_types=1);

namespace OasFake;

use function array_reverse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Runs the configured PSR-15 middleware chain around a resolved response.
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
     * Process a request/response pair through the configured middleware.
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->middleware === []) {
            return $response;
        }

        $chain = new ResolvedResponseRequestHandler($response);
        foreach (array_reverse($this->middleware) as $middleware) {
            $chain = new MiddlewareRequestHandler($middleware, $chain);
        }

        return $chain->handle($request);
    }
}
