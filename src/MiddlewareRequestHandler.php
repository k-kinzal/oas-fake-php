<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler that invokes one middleware and delegates to the next handler.
 */
final class MiddlewareRequestHandler implements RequestHandlerInterface
{
    /**
     * Create a handler that runs one middleware before delegating to the next handler.
     */
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $next,
    ) {
    }

    /**
     * Process the request through this middleware.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
    }
}
