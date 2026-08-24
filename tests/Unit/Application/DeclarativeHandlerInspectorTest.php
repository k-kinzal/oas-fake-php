<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\DeclarativeHandlerInspector;
use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\Server;
use OasFake\Testing\InspectorServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
final class DeclarativeHandlerInspectorTest extends TestCase
{
    /**
     */
    public function testIsCandidateExcludesInvalidPublicMethods(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets', 'GET'));
        self::assertNotNull($handlers->find('requestOnly', '/pets', 'GET'));
        self::assertNotNull($handlers->find('untypedReturn', '/pets', 'GET'));
        self::assertNull($handlers->find('invalid', '/invalid', 'GET'));
        self::assertNull($handlers->find('staticHandler', '/invalid', 'GET'));
        self::assertNull($handlers->find('missingRequest', '/invalid', 'GET'));
        self::assertNull($handlers->find('tooManyParameters', '/invalid', 'GET'));
        self::assertNull($handlers->find('builtinRequest', '/invalid', 'GET'));
        self::assertNull($handlers->find('untypedRequest', '/invalid', 'GET'));
        self::assertNull($handlers->find('requiredResponse', '/invalid', 'GET'));
        self::assertNull($handlers->find('builtinResponse', '/invalid', 'GET'));
        self::assertNull($handlers->find('invalidReturn', '/invalid', 'GET'));
        self::assertNull($handlers->find('resolveMiddleware', '/invalid', 'GET'));
    }

    /**
     */
    public function testRouteRegistersDeclaredMapping(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets', 'GET'));
    }
}
