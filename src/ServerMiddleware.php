<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Provides the subclass extension point for declarative server middleware.
 */
trait ServerMiddleware
{
    /**
     * Return middleware declared by a server subclass.
     *
     * @return list<MiddlewareInterface>
     */
    protected static function middleware(): array
    {
        return [];
    }
}
