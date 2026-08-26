<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\DeclarativeHandlerInspector;
use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\Server;
use OasFake\Testing\HandlerShapedConstructorServer;
use OasFake\Testing\InspectorServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @covers \OasFake\DeclarativeHandlerInspector
 *
 * @uses \OasFake\HandlerTypeMatcher
 * @uses \OasFake\DeclarativeHandlerRegistrar
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\Route
 * @uses \OasFake\Server
 * @uses \OasFake\ServerRuntime
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 */
#[CoversClass(DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerTypeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
final class DeclarativeHandlerInspectorTest extends TestCase
{
    /**
     * @throws ReflectionException when the exercised contract propagates it
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
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testRouteRegistersDeclaredMapping(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testIsCandidateExcludesFrameworkMethodsAndConstructors(): void
    {
        $frameworkHandlers = new HandlerMap();
        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $frameworkHandlers);

        $constructorHandlers = new HandlerMap();
        $server = new HandlerShapedConstructorServer(new ServerRequest('GET', '/pets'));
        (new DeclarativeHandlerRegistrar())->register($server, $constructorHandlers);

        self::assertNull($frameworkHandlers->find('withMode', '/invalid', 'GET'));
        self::assertNull($constructorHandlers->find('__construct', '/invalid', 'GET'));
    }
}
