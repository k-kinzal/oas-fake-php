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

    public function testResolveKeepsPathWhenServerUrlDoesNotMatchRequest(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://other.example.com/v1/pets');

        self::assertSame('/v1/pets', (new OperationPathResolver())->resolve($schema, $request));
    }
}
