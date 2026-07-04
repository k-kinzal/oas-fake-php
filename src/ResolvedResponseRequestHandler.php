<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler that returns an already resolved response.
 */
final class ResolvedResponseRequestHandler implements RequestHandlerInterface
{
    /**
     * Create a handler for an already resolved response.
     */
    public function __construct(private ResponseInterface $response)
    {
    }

    /**
     * Return the resolved response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
