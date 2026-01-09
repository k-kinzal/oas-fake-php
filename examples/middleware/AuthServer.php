<?php

declare(strict_types=1);

namespace OasFake\Examples\Middleware;

use OasFake\Server;
use Psr\Http\Server\MiddlewareInterface;

final class AuthServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static bool $VALIDATE_REQUESTS = false;

    /** @var list<MiddlewareInterface> */
    private static array $staticMiddleware = [];

    public static function setStaticMiddleware(MiddlewareInterface ...$middleware): void
    {
        self::$staticMiddleware = array_values($middleware);
    }

    public static function clearStaticMiddleware(): void
    {
        self::$staticMiddleware = [];
    }

    /** @return list<MiddlewareInterface> */
    protected static function middleware(): array
    {
        return self::$staticMiddleware;
    }
}
