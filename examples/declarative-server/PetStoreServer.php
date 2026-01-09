<?php

declare(strict_types=1);

namespace OasFake\Examples\DeclarativeServer;

use GuzzleHttp\Psr7\Response;
use OasFake\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PetStoreServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static string $MODE = 'fake';
    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    protected static array $FAKER_OPTIONS = ['alwaysFakeOptionals' => true];

    /** @var list<string> */
    public static array $calledOperations = [];

    public static function resetCalls(): void
    {
        self::$calledOperations = [];
    }

    /** operationId: listPets */
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        self::$calledOperations[] = 'listPets';

        return $response ?? new Response(200, ['Content-Type' => 'application/json'], '[]');
    }

    /** operationId: createPet */
    public function createPet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        self::$calledOperations[] = 'createPet';

        $body = json_decode((string) $request->getBody(), true);

        return new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
            'id' => 42,
            'name' => $body['name'] ?? 'Unknown',
            'species' => $body['species'] ?? 'Unknown',
        ]));
    }

    /** operationId: showPetById */
    public function showPetById(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        self::$calledOperations[] = 'showPetById';

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'id' => 1,
            'name' => 'Buddy',
            'species' => 'dog',
        ]));
    }

    /** operationId: updatePet */
    public function updatePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        self::$calledOperations[] = 'updatePet';

        $body = json_decode((string) $request->getBody(), true);

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'id' => 1,
            'name' => $body['name'] ?? 'Updated',
            'species' => $body['species'] ?? 'dog',
        ]));
    }

    /** operationId: deletePet */
    public function deletePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        self::$calledOperations[] = 'deletePet';

        return new Response(204);
    }
}
