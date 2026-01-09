<?php

declare(strict_types=1);

namespace OasFake\Examples\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestRecorderMiddleware implements MiddlewareInterface
{
    /** @var list<array{method: string, path: string}> */
    private array $calls = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->calls[] = [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
        ];

        return $handler->handle($request);
    }

    /** @return list<array{method: string, path: string}> */
    public function getCalls(): array
    {
        return $this->calls;
    }
}
