<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContextResolver;
use OasFake\FakeDataSource;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClassesThatImplementInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversClassesThatImplementInterface(FakeDataSource::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContextResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class FakeDataSourceTest extends TestCase
{
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
