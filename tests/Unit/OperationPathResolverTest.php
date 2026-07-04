<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\OperationPathResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationPathResolver::class)]
final class OperationPathResolverTest extends TestCase
{
    public function testResolveReturnsPathWhenServerHasNoBasePath(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets/1');

        self::assertSame('/pets/1', (new OperationPathResolver())->resolve($schema, $request));
    }

    public function testResolveStripsMatchingServerBasePath(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.versioned.example.com/v1/pets');

        self::assertSame('/pets', (new OperationPathResolver())->resolve($schema, $request));
    }

    public function testResolveUsesMostSpecificServerBasePath(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Specific Server API
              version: 1.0.0
            servers:
              - url: https://api.example.com
            paths:
              /pets:
                servers:
                  - url: https://api.example.com/v1
                get:
                  operationId: listPets
                  responses:
                    '200':
                      description: OK
            YAML);
        $request = new ServerRequest('GET', 'https://api.example.com/v1/pets');

        self::assertSame('/pets', (new OperationPathResolver())->resolve($schema, $request));
    }

    public function testResolveWithServerUrlReturnsMatchedServerUrl(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.versioned.example.com/v1/pets');

        self::assertSame(
            [
                'path' => '/pets',
                'serverUrl' => 'https://api.versioned.example.com/v1',
            ],
            (new OperationPathResolver())->resolveWithServerUrl($schema, $request),
        );
    }

    public function testResolveKeepsPathWhenServerUrlDoesNotMatchRequest(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://other.example.com/v1/pets');

        self::assertSame('/v1/pets', (new OperationPathResolver())->resolve($schema, $request));
    }
}
