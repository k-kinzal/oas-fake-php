<?php

declare(strict_types=1);

namespace OasFake\Examples\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class OrderTrackingMiddleware implements MiddlewareInterface
{
    /** @var list<int> */
    private static array $executionOrder = [];

    private int $order;

    public function __construct(int $order)
    {
        $this->order = $order;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        self::$executionOrder[] = $this->order;

        return $handler->handle($request);
    }

    /** @return list<int> */
    public static function getExecutionOrder(): array
    {
        return self::$executionOrder;
    }

    public static function reset(): void
    {
        self::$executionOrder = [];
    }
}
