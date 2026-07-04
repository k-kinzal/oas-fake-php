<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeDataContext::class)]
final class FakeDataContextTest extends TestCase
{
    public function testSchemaReturnsSourceSchema(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($schema, $context->schema());
    }

    public function testOperationLookupReturnsSharedLookup(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertSame($context->operationLookup(), $context->operationLookup());
        self::assertNotNull($context->operationLookup()->findByOperationId('listPets'));
    }

    public function testFakerOptionsReturnsConfiguredOptions(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema, ['alwaysFakeOptionals' => true]);

        self::assertSame(['alwaysFakeOptionals' => true], $context->fakerOptions());
    }

    public function testMockRequestGeneratesData(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockRequest('/pets', 'POST'));
    }

    public function testMockResponseGeneratesData(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);

        self::assertIsArray($context->mockResponse('/pets', 'GET', 200));
    }
}
