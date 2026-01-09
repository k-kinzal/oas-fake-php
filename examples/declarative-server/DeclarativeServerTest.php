<?php

declare(strict_types=1);

namespace OasFake\Examples\DeclarativeServer;

use GuzzleHttp\Client;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class DeclarativeServerTest extends TestCase
{
    protected function setUp(): void
    {
        PetStoreServer::resetCalls();
    }

    protected function tearDown(): void
    {
        OasFake::stop();
        PetStoreServer::resetCalls();
    }

    public function testListPetsOperationIdMethod(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $response = $client->get('/pets');

        self::assertSame(200, $response->getStatusCode());
        self::assertContains('listPets', PetStoreServer::$calledOperations);
    }

    public function testCreatePetOperationIdMethod(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $response = $client->post('/pets', [
            'json' => ['name' => 'Fluffy', 'species' => 'cat'],
        ]);

        self::assertSame(201, $response->getStatusCode());
        self::assertContains('createPet', PetStoreServer::$calledOperations);

        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Fluffy', $body['name']);
        self::assertSame('cat', $body['species']);
    }

    public function testShowPetByIdWithPathParam(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $response = $client->get('/pets/1');

        self::assertSame(200, $response->getStatusCode());
        self::assertContains('showPetById', PetStoreServer::$calledOperations);

        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Buddy', $body['name']);
    }

    public function testUpdatePetOperationIdMethod(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $response = $client->put('/pets/1', [
            'json' => ['name' => 'Rex', 'species' => 'dog'],
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains('updatePet', PetStoreServer::$calledOperations);

        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Rex', $body['name']);
    }

    public function testDeletePetRouteAttribute(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $response = $client->delete('/pets/1');

        self::assertSame(204, $response->getStatusCode());
        self::assertContains('deletePet', PetStoreServer::$calledOperations);
    }

    public function testStartReturnsServerInstance(): void
    {
        $server = OasFake::start(PetStoreServer::class);

        self::assertInstanceOf(PetStoreServer::class, $server);
    }

    public function testAllOperationsTracked(): void
    {
        OasFake::start(PetStoreServer::class);
        $client = new Client(['base_uri' => 'https://api.petstore.example.com']);

        $client->get('/pets');
        $client->post('/pets', ['json' => ['name' => 'Max', 'species' => 'dog']]);
        $client->get('/pets/1');
        $client->put('/pets/1', ['json' => ['name' => 'Max Jr', 'species' => 'dog']]);
        $client->delete('/pets/1');

        self::assertSame(
            ['listPets', 'createPet', 'showPetById', 'updatePet', 'deletePet'],
            PetStoreServer::$calledOperations,
        );
    }
}
