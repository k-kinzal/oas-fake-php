<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContextResolver;
use OasFake\FakeDataSource;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeDataContextResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class FakeDataSourceTest extends TestCase
{
    public function testSchemaIsResolvedWithExplicitGenerationPolicy(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        $context = (new FakeDataContextResolver())->resolve($schema, ['minItems' => 2]);

        self::assertSame($schema, $context->schema());
        self::assertSame(['minItems' => 2], $context->fakerOptions());
    }

    public function testResolvedContextRetainsItsIdentity(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new \OasFake\FakeDataContext($schema, ['maxItems' => 3]);

        self::assertSame($context, (new FakeDataContextResolver())->resolve($context, ['minItems' => 2]));
    }

    public function testSchemaSuppliesGenerationContract(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $source = new class ($schema) implements FakeDataSource {
            public function __construct(private Schema $schema)
            {
            }

            public function schema(): Schema
            {
                return $this->schema;
            }

            public function fakerOptions(): array
            {
                return ['alwaysFakeOptionals' => true];
            }
        };

        $context = (new FakeDataContextResolver())->resolve($source);

        self::assertSame($schema, $context->schema());
    }

    public function testFakerOptionsSuppliesGenerationPolicy(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $source = new class ($schema) implements FakeDataSource {
            public function __construct(private Schema $schema)
            {
            }

            public function schema(): Schema
            {
                return $this->schema;
            }

            public function fakerOptions(): array
            {
                return ['alwaysFakeOptionals' => true];
            }
        };

        $context = (new FakeDataContextResolver())->resolve($source);

        self::assertSame(['alwaysFakeOptionals' => true], $context->fakerOptions());
    }
}
