<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\HandlerTypeMatcher;
use OasFake\Testing\InspectorServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

/**
 * @covers \OasFake\HandlerTypeMatcher
 *
 * @uses \OasFake\DeclarativeHandlerInspector
 * @uses \OasFake\DeclarativeHandlerRegistrar
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\Route
 * @uses \OasFake\Server
 * @uses \OasFake\ServerRuntime
 */
#[CoversClass(HandlerTypeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
final class HandlerTypeMatcherTest extends TestCase
{
    public function testAllowsNullableRejectsAMissingTypeDeclaration(): void
    {
        self::assertFalse((new HandlerTypeMatcher())->allowsNullable(null, ResponseInterface::class));
    }

    /**
     * @throws ReflectionException when a handler declaration cannot be inspected
     */
    public function testAllowsSelectsOnlyCompatibleHandlerTypes(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $handlers);

        self::assertNotNull($handlers->find('requestOnly', '/pets', 'GET'));
        self::assertNull($handlers->find('builtinRequest', '/invalid', 'GET'));
        self::assertNull($handlers->find('builtinResponse', '/invalid', 'GET'));
    }

    /**
     * @throws ReflectionException when a handler declaration cannot be inspected
     */
    public function testAllowsNullableRequiresAnOptionalResponseType(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new InspectorServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets', 'GET'));
        self::assertNull($handlers->find('requiredResponse', '/invalid', 'GET'));
    }
}
