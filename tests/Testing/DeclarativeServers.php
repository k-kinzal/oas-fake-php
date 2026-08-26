<?php

declare(strict_types=1);

namespace OasFake\Testing;

use GuzzleHttp\Psr7\Response;
use OasFake\Route;
use OasFake\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Declares static server configuration for inheritance-contract tests.
 */
class DeclarativeTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';
    protected static string $MODE = 'record';
    protected static string $CASSETTE_PATH = '/custom/cassettes';
    protected static bool $VALIDATE_REQUESTS = false;
    protected static bool $VALIDATE_RESPONSES = false;

    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    protected static array $FAKER_OPTIONS = ['alwaysFakeOptionals' => true];
}

/**
 * Declares an operationId-named response method.
 */
class OperationIdTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    /**
     * Return the generated response for listPets.
     */
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200, [], '[]');
    }
}

/**
 * Declares an attribute-routed response method.
 */
class RouteTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    /**
     * Remove one pet through the declared route.
     */
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(204);
    }
}

/**
 * Supplies a stable class name for cassette-name normalization.
 */
class CassetteNameTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';
}

/**
 * Declares a method that deliberately does not satisfy the handler signature.
 */
class InvalidSignatureOperationIdServer extends Server
{
    /**
     * Return a non-response value so registration must skip this method.
     */
    public function listPets(): string
    {
        return 'not a response';
    }
}

/**
 * Declares a schema-aware operationId handler.
 */
class SchemaAwareOperationServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    /**
     * Return the declarative listPets response.
     */
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(200, [], '[{"name":"Declarative operation"}]');
    }
}

/**
 * Declares a schema-aware route handler.
 */
class SchemaAwareRouteServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    /**
     * Return a no-content response for a matching schema route.
     */
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(204);
    }
}

/**
 * Declares a route that is absent from the configured schema.
 */
class UnknownRouteDeclarativeServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    /**
     * Return a response that must never be registered for the schema.
     */
    #[Route(method: 'GET', path: '/unknown')]
    public function unknown(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(200, [], 'Unknown declarative route');
    }
}
