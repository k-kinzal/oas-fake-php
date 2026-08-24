<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Server;
use OasFake\ServerMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
#[UsesTrait(ServerMiddleware::class)]
final class ServerMiddlewareTest extends TestCase
{
    public function testMiddlewareDefaultsToEmptyList(): void
    {
        $server = new class () extends Server {
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
