<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Operation;
use OasFake\OperationDefinition;
use OasFake\OperationLookup;
use OasFake\OperationResponseResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationResponseResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testDefaultsWhenNoSuccessfulResponseIsDeclared(): void
    {
        $definition = new OperationDefinition('/pets', 'get', 'listPets', new Operation(['responses' => []]), []);
        $resolver = new OperationResponseResolver();

        self::assertSame(200, $resolver->defaultStatusCode($definition));
        self::assertTrue($resolver->hasSuccessfulBody($definition));
        self::assertNull($resolver->forStatus($definition, 404));
        self::assertSame('application/json', $resolver->mediaType($definition, 404));
        self::assertNull($resolver->schema($definition, 404, 'application/json'));
    }

    public function testDefaultStatusCodeReturnsFirstSuccessfulResponse(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertSame(201, (new OperationResponseResolver())->defaultStatusCode($definition));
    }

    public function testHasSuccessfulBodyReflectsResponseContent(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('deletePet');
        self::assertNotNull($definition);

        self::assertFalse((new OperationResponseResolver())->hasSuccessfulBody($definition));
    }

    public function testForStatusReturnsExactResponse(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->forStatus($definition, 201));
    }

    public function testMediaTypePrefersJson(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertSame('application/json', (new OperationResponseResolver())->mediaType($definition, 200));
    }

    public function testSchemaReturnsMediaTypeSchema(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->schema($definition, 200, 'application/json'));
        self::assertNull((new OperationResponseResolver())->schema($definition, 200, 'text/plain'));
    }
}
