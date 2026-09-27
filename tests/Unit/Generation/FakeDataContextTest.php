<?php

declare(strict_types=1);

namespace Tests\Unit;

use cebe\openapi\spec\Schema as CebeSchema;
use OasFake\FakeDataContext;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\FakeDataContext
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationDefinition
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationDefinitionResolver
 */
#[CoversClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinitionResolver::class)]
final class FakeDataContextTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testSchemaReturnsSourceSchema(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($schema, $context->schema());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testOperationLookupReturnsSharedLookup(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($context->operationLookup(), $context->operationLookup());
        self::assertNotNull($context->operationLookup()->findByOperationId('listPets'));
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFakerOptionsReturnsConfiguredOptions(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema, ['alwaysFakeOptionals' => true]);

        self::assertSame(['alwaysFakeOptionals' => true], $context->fakerOptions());
    }

    /**
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the fixture request is missing
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testMockRequestGeneratesData(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockRequest('/pets', 'POST'));
    }

    /**
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the fixture response is missing
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testMockResponseGeneratesData(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockResponse('/pets', 'GET', 200));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture is invalid
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testMockSchemaGeneratesData(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);
        $valueSchema = new CebeSchema([
            'type' => 'string',
            'enum' => ['ok'],
        ]);

        self::assertSame('ok', $context->mockSchema($valueSchema));
    }
}
