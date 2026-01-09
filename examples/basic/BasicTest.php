<?php

declare(strict_types=1);

namespace OasFake\Examples\Basic;

use GuzzleHttp\Client;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testStartAndGetFakeResponse(): void
    {
        OasFake::start(BasicServer::class);

        $client = new Client(['base_uri' => 'https://api.example.com']);
        $response = $client->get('/users');

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
    }

    public function testStartReturnsServerInstance(): void
    {
        $server = OasFake::start(BasicServer::class);

        self::assertInstanceOf(BasicServer::class, $server);
    }

    public function testStopStopsServer(): void
    {
        $server = OasFake::start(BasicServer::class);
        OasFake::stop();

        self::assertFalse($server->isRunning());
    }

    public function testPostCreateUser(): void
    {
        OasFake::start(BasicServer::class);

        $client = new Client(['base_uri' => 'https://api.example.com']);
        $response = $client->post('/users', [
            'json' => ['name' => 'John Doe', 'email' => 'john@example.com'],
        ]);

        self::assertSame(201, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('name', $body);
        self::assertArrayHasKey('email', $body);
    }
}
