<?php

declare(strict_types=1);

namespace OasFake\Testing;

use GuzzleHttp\Psr7\Response;
use OasFake\Route;
use OasFake\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Declares valid and invalid methods for inspector behavior.
 */
final class InspectorServer extends Server
{
    /**
     * Return a response through a valid route declaration.
     */
    #[Route(method: 'GET', path: '/pets')]
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }

    /**
     * Return an invalid handler result that inspection must reject.
     */
    public function invalid(): string
    {
        return 'invalid';
    }
}

/**
 * Declares operation handlers with and without schema matches.
 */
class RegistrarOperationServer extends Server
{
    /**
     * Return the known listPets operation response.
     */
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }

    /**
     * Return a response for an operation absent from the schema.
     */
    public function helperOperation(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }
}

/**
 * Declares a valid route handler.
 */
class RegistrarRouteServer extends Server
{
    /**
     * Return a no-content response for the declared route.
     */
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(204);
    }
}

/**
 * Declares an operation method with an invalid second parameter.
 */
class RegistrarInvalidParameterServer extends Server
{
    /**
     * Return a response through a signature that inspection must reject.
     */
    public function listPets(ServerRequestInterface $request, string $unexpected): ResponseInterface
    {
        return new Response(200, [], $unexpected);
    }
}

/**
 * Declares an attributed method with an invalid request signature.
 */
class RegistrarInvalidRouteServer extends Server
{
    /**
     * Return a response without accepting an intercepted request.
     */
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(): ResponseInterface
    {
        return new Response(204);
    }
}

/**
 * Declares a route absent from the schema.
 */
class RegistrarUnknownRouteServer extends Server
{
    /**
     * Return a response for the unknown route.
     */
    #[Route(method: 'GET', path: '/unknown')]
    public function unknown(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200);
    }
}
