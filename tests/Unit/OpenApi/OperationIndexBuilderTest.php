<?php

declare(strict_types=1);

namespace Tests\Unit;

use OasFake\OperationIndexBuilder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationIndexBuilder
 *
 * @uses \OasFake\OperationDefinitionResolver
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationDefinition
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 */
#[CoversClass(OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinitionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationIndexBuilderTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testBuildIndexesIdsPathsAndMergedParameters(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $indexes = (new OperationIndexBuilder())->build($schema);

        self::assertArrayHasKey('listPets', $indexes['byOperationId']);
        self::assertArrayHasKey('get:/pets', $indexes['byPathMethod']);
        self::assertSame('petId', $indexes['byOperationId']['getPetById']->parameters()[0]->name);
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
    public function testBuildContinuesPastMethodsMissingFromAPath(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info: {title: Commands, version: 1.0.0}
            paths:
              /commands:
                post:
                  operationId: createCommand
                  responses:
                    '202': {description: Accepted}
            YAML);

        $indexes = (new OperationIndexBuilder())->build($schema);

        self::assertArrayHasKey('createCommand', $indexes['byOperationId']);
        self::assertArrayHasKey('post:/commands', $indexes['byPathMethod']);
    }
}
