<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OperationIndexBuilder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationIndexBuilder
 *
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 */
#[CoversClass(OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationIndexBuilderTest extends TestCase
{
    public function testBuildIndexesIdsPathsAndMergedParameters(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $indexes = (new OperationIndexBuilder())->build($schema);

        self::assertArrayHasKey('listPets', $indexes['byOperationId']);
        self::assertArrayHasKey('get:/pets', $indexes['byPathMethod']);
        self::assertSame('petId', $indexes['byOperationId']['getPetById']->parameters[0]->name);
    }
}
