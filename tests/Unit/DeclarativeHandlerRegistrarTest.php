<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\Schema;
use OasFake\Testing\RegistrarInvalidParameterServer;
use OasFake\Testing\RegistrarInvalidRouteServer;
use OasFake\Testing\RegistrarOperationServer;
use OasFake\Testing\RegistrarRouteServer;
use OasFake\Testing\RegistrarUnknownRouteServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[CoversClass(DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Server::class)]
final class DeclarativeHandlerRegistrarTest extends TestCase
{
    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterAddsOperationIdHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterWithSchemaSkipsUnknownOperationIdHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers, $schema);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
        self::assertNull($handlers->find('helperOperation', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterAddsRouteHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarRouteServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }

    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterWithSchemaSkipsUnknownRouteHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarUnknownRouteServer(), $handlers, $schema);

        self::assertNull($handlers->find('', '/unknown', 'GET'));
    }

    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterSkipsMethodsWithExtraRequiredParameters(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidParameterServer(), $handlers);

        self::assertNull($handlers->find('listPets', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when handler metadata cannot be read
     */
    public function testRegisterSkipsRouteMethodsWithInvalidSignature(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidRouteServer(), $handlers);

        self::assertNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }
}
