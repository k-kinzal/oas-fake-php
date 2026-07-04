<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\DeclarativeHandlerRegistrar;
use OasFake\HandlerMap;
use OasFake\Route;
use OasFake\Schema;
use OasFake\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(DeclarativeHandlerRegistrar::class)]
final class DeclarativeHandlerRegistrarTest extends TestCase
{
    public function testRegisterAddsOperationIdHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
    }

    public function testRegisterWithSchemaSkipsUnknownOperationIdHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarOperationServer(), $handlers, $schema);

        self::assertNotNull($handlers->find('listPets', '/pets', 'GET'));
        self::assertNull($handlers->find('helperOperation', '/pets', 'GET'));
    }

    public function testRegisterAddsRouteHandler(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarRouteServer(), $handlers);

        self::assertNotNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }

    public function testRegisterWithSchemaSkipsUnknownRouteHandler(): void
    {
        $handlers = new HandlerMap();
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        (new DeclarativeHandlerRegistrar())->register(new RegistrarUnknownRouteServer(), $handlers, $schema);

        self::assertNull($handlers->find('', '/unknown', 'GET'));
    }

    public function testRegisterSkipsMethodsWithExtraRequiredParameters(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidParameterServer(), $handlers);

        self::assertNull($handlers->find('listPets', '/pets', 'GET'));
    }

    public function testRegisterSkipsRouteMethodsWithInvalidSignature(): void
    {
        $handlers = new HandlerMap();

        (new DeclarativeHandlerRegistrar())->register(new RegistrarInvalidRouteServer(), $handlers);

        self::assertNull($handlers->find('', '/pets/1', 'DELETE', '/pets/{petId}'));
    }
}

class RegistrarOperationServer extends Server
{
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }

    public function helperOperation(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }
}

class RegistrarRouteServer extends Server
{
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(204);
    }
}

class RegistrarInvalidParameterServer extends Server
{
    public function listPets(ServerRequestInterface $request, string $unexpected): ResponseInterface
    {
        return new Response(200, [], $unexpected);
    }
}

class RegistrarInvalidRouteServer extends Server
{
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(): ResponseInterface
    {
        return new Response(204);
    }
}

class RegistrarUnknownRouteServer extends Server
{
    #[Route(method: 'GET', path: '/unknown')]
    public function unknown(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }
}
