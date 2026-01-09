<?php

declare(strict_types=1);

namespace OasFake\Examples\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddleware implements MiddlewareInterface
{
    private string $requestId;

    public function __construct(?string $requestId = null)
    {
        $this->requestId = $requestId ?? bin2hex(random_bytes(8));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader('X-Request-Id', $this->requestId);
    }
}
