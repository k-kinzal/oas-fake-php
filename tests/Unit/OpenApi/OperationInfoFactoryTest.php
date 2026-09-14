<?php

declare(strict_types=1);

namespace Tests\Unit;

use OasFake\OperationIndexBuilder;
use OasFake\OperationInfoFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationInfoFactory
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 */
#[CoversClass(OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationInfoFactoryTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testCreatePreservesIndexedOperationMetadata(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationIndexBuilder())->build($schema)['byOperationId']['getPetById'];

        self::assertSame('/pets/{petId}', $operation->pathPattern);
        self::assertSame('get', $operation->method);
        self::assertSame('getPetById', $operation->operationId);
    }
}
