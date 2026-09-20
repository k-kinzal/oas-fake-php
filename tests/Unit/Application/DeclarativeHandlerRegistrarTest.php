<?php

declare(strict_types=1);

namespace Tests\Unit;

use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tests\Fixtures\RegistrarContinuationServer;
use Tests\Fixtures\RegistrarInvalidParameterServer;
use Tests\Fixtures\RegistrarInvalidRouteServer;
use Tests\Fixtures\RegistrarOperationServer;
use Tests\Fixtures\RegistrarRouteServer;
use Tests\Fixtures\RegistrarUnknownRouteServer;

/**
 * @covers \OasFake\DeclarativeHandlerRegistrar
 *
 * @uses \OasFake\DeclarativeHandlerInspector
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationDefinition
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Route
 * @uses \OasFake\Schema
 * @uses \OasFake\Server
 * @uses \OasFake\HandlerTypeMatcher
 * @uses \OasFake\OperationDefinitionResolver
 * @uses \OasFake\ServerRuntime
 */
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerTypeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinitionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
final class DeclarativeHandlerRegistrarTest extends TestCase
{
    /**
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testRegisterAddsOperationIdHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRegisterWithSchemaSkipsUnknownOperationIdHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers, $schema);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
        self::assertNull($handlers->find('helperOperation', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testRegisterAddsRouteHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarRouteServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRegisterWithSchemaSkipsUnknownRouteHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarUnknownRouteServer(), $handlers, $schema);

        self::assertNull($handlers->find('', '/unknown', 'GET'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testRegisterSkipsMethodsWithExtraRequiredParameters(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidParameterServer(), $handlers);

        self::assertNull($handlers->find('listPets', '/pets', 'GET'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     */
    public function testRegisterSkipsRouteMethodsWithInvalidSignature(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidRouteServer(), $handlers);

        self::assertNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRegisterContinuesAfterEverySkippedDeclaration(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarContinuationServer(), $handlers, $schema);

        self::assertNotNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
        self::assertNull($handlers->find('', '/unknown', 'GET'));
        self::assertNull($handlers->find('helperOperation', '/pets', 'GET'));
    }
}
