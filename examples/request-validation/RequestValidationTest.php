<?php

declare(strict_types=1);

namespace OasFake\Examples\RequestValidation;

use GuzzleHttp\Client;
use OasFake\Exception\ValidationException;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class RequestValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testValidRequestSucceeds(): void
    {
        OasFake::start(StrictServer::class);
        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        $response = $client->post('/users', [
            'json' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'role' => 'admin',
                'birthDate' => '1990-01-15',
            ],
        ]);

        self::assertSame(201, $response->getStatusCode());
    }

    public function testMissingRequiredFieldThrowsValidationException(): void
    {
        OasFake::start(StrictServer::class);
        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        $this->expectException(ValidationException::class);

        $client->post('/users', [
            'json' => ['name' => 'Bob'],
        ]);
    }

    public function testInvalidEnumValueThrowsValidationException(): void
    {
        OasFake::start(StrictServer::class);
        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        $this->expectException(ValidationException::class);

        $client->post('/users', [
            'json' => [
                'name' => 'Charlie',
                'email' => 'charlie@example.com',
                'role' => 'superadmin',
            ],
        ]);
    }

    public function testInvalidEmailFormatThrowsValidationException(): void
    {
        OasFake::start(StrictServer::class);
        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        $this->expectException(ValidationException::class);

        $client->post('/users', [
            'json' => [
                'name' => 'Dave',
                'email' => 'not-an-email',
                'role' => 'viewer',
            ],
        ]);
    }

    public function testDisabledValidationAllowsInvalidRequest(): void
    {
        OasFake::start(StrictServer::class, static fn (StrictServer $s) => $s
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('createUser', 201, [
                'id' => 1,
                'name' => 'Eve',
                'email' => 'eve@example.com',
                'role' => 'viewer',
            ]));

        $client = new Client(['base_uri' => 'https://api.strict.example.com']);
        $response = $client->post('/users', [
            'json' => ['name' => 'Eve'],
        ]);

        self::assertSame(201, $response->getStatusCode());
    }

    public function testResponseValidationWithInvalidStub(): void
    {
        OasFake::start(StrictServer::class, static fn (StrictServer $s) => $s
            ->withResponse('getUser', 200, ['invalid' => 'response']));

        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        $this->expectException(ValidationException::class);

        $client->get('/users/1');
    }

    public function testValidationExceptionHasDetails(): void
    {
        OasFake::start(StrictServer::class);
        $client = new Client(['base_uri' => 'https://api.strict.example.com']);

        try {
            $client->post('/users', [
                'json' => ['name' => 'Frank'],
            ]);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            self::assertNotNull($e->getValidationError());
            self::assertStringContainsString('validation failed', strtolower($e->getMessage()));
        }
    }
}
