<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\OperationPathResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationPathResolver
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\Schema
 * @uses \OasFake\ServerUrlMatcher
 */
#[CoversClass(OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
final class OperationPathResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testResolveReturnsPathWhenServerHasNoBasePath(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets/1');

        self::assertSame('/pets/1', (new OperationPathResolver())->resolve($schema, $request));
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testResolveStripsMatchingServerBasePath(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.versioned.example.com/v1/pets');

        self::assertSame('/pets', (new OperationPathResolver())->resolve($schema, $request));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
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

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
    public function testResolveUsesTheMostSpecificOfMultipleEffectiveServerUrls(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info: {title: Multi Server API, version: 1.0.0}
            servers:
              - url: https://api.example.com
            paths:
              /health:
                get:
                  operationId: health
                  responses:
                    '204': {description: Healthy}
              /pets:
                servers:
                  - url: https://api.example.com/v1
                get:
                  operationId: listPets
                  responses:
                    '204': {description: No content}
            YAML);
        $request = new ServerRequest('GET', 'https://api.example.com/v1/pets');

        self::assertSame(
            ['path' => '/pets', 'serverUrl' => 'https://api.example.com/v1'],
            (new OperationPathResolver())->resolveWithServerUrl($schema, $request),
        );
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testResolveWithServerUrlReturnsMatchedServerUrl(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://api.versioned.example.com/v1/pets');

        self::assertSame(
            [
                'path' => '/pets',
                'serverUrl' => 'https://api.versioned.example.com/v1',
            ],
            (new OperationPathResolver())->resolveWithServerUrl($schema, $request),
        );
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testResolveKeepsPathWhenServerUrlDoesNotMatchRequest(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml');
        $request = new ServerRequest('GET', 'https://other.example.com/v1/pets');

        self::assertSame('/v1/pets', (new OperationPathResolver())->resolve($schema, $request));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
    public function testResolveContinuesPastANonMatchingServer(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info: {title: Multi-host API, version: 1.0.0}
            servers:
              - url: https://other.example.com/v1
              - url: https://api.example.com/v1
            paths:
              /pets:
                get:
                  operationId: listPets
                  responses:
                    '200': {description: OK}
            YAML);
        $request = new ServerRequest('GET', 'https://api.example.com/v1/pets');

        self::assertSame('/pets', (new OperationPathResolver())->resolve($schema, $request));
    }
}
