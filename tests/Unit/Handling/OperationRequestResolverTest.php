<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\OperationLookup;
use OasFake\OperationPathResolver;
use OasFake\OperationRequestResolver;
use OasFake\Schema;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationRequestResolver
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\OperationPathResolver
 * @uses \OasFake\OperationRequest
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class OperationRequestResolverTest extends TestCase
{
    public function testResolveUsesEffectiveServerPathAndOperation(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $resolver = new OperationRequestResolver(
            $schema,
            new OperationLookup($schema),
            new OperationPathResolver(),
            new Validator($schema),
            false,
        );

        $resolved = $resolver->resolve(new ServerRequest('GET', 'https://api.petstore.example.com/pets'));

        self::assertSame('/pets', $resolved->path);
        self::assertSame('listPets', $resolved->definition?->operationId);
        self::assertSame('/pets', $resolved->address?->path());
    }

    public function testResolveRejectsAnOperationOutsideItsEffectiveServer(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info: {title: Scoped API, version: 1.0.0}
            servers:
              - url: https://api.example.com/v1
            paths:
              /health:
                get:
                  operationId: health
                  responses:
                    '204': {description: Healthy}
              /pets:
                get:
                  operationId: listPets
                  servers:
                    - url: https://api.example.com
                  responses:
                    '200': {description: OK}
            YAML);
        $resolver = new OperationRequestResolver(
            $schema,
            new OperationLookup($schema),
            new OperationPathResolver(),
            new Validator($schema),
            false,
        );

        $resolved = $resolver->resolve(new ServerRequest('GET', 'https://api.example.com/v1/pets'));

        self::assertSame('/pets', $resolved->path);
        self::assertNull($resolved->definition);
        self::assertNull($resolved->address);
    }
}
