<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\ServerMiddleware;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(ServerMiddleware::class)]
final class ServerMiddlewareTest extends TestCase
{
    public function testMiddlewareDefaultsToEmptyList(): void
    {
        $server = new class () {
            use ServerMiddleware;

            /**
             * Return the middleware declared by the trait.
             *
             * @return list<\Psr\Http\Server\MiddlewareInterface>
             */
            public function declaredMiddleware(): array
            {
                return self::middleware();
            }
        };

        self::assertSame([], $server->declaredMiddleware());
    }
}
