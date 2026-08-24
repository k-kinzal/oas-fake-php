<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Schema as CebeSchema;
use OasFake\FakeDataContext;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class FakeDataContextTest extends TestCase
{
    public function testSchemaReturnsSourceSchema(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($schema, $context->schema());
    }

    public function testOperationLookupReturnsSharedLookup(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($context->operationLookup(), $context->operationLookup());
        self::assertNotNull($context->operationLookup()->findByOperationId('listPets'));
    }

    public function testFakerOptionsReturnsConfiguredOptions(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema, ['alwaysFakeOptionals' => true]);

        self::assertSame(['alwaysFakeOptionals' => true], $context->fakerOptions());
    }

    /**
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the fixture request is missing
     */
    public function testMockRequestGeneratesData(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockRequest('/pets', 'POST'));
    }

    /**
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the fixture response is missing
     */
    public function testMockResponseGeneratesData(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockResponse('/pets', 'GET', 200));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture is invalid
     */
    public function testMockSchemaGeneratesData(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);
        $valueSchema = new CebeSchema([
            'type' => 'string',
            'enum' => ['ok'],
        ]);

        self::assertSame('ok', $context->mockSchema($valueSchema));
    }
}
