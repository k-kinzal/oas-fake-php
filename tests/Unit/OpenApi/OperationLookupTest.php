<?php

declare(strict_types=1);

namespace Tests\Unit;

use OasFake\OperationLookup;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationLookup
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationDefinition
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\RequestPathMatcher
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationDefinitionResolver
 */
#[CoversClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\RequestPathMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinitionResolver::class)]
final class OperationLookupTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByOperationIdReturnsInfo(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern());
        self::assertSame('get', $info->method());
        self::assertSame('listPets', $info->operationId());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByOperationIdReturnsNullForUnknown(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('nonexistent');

        self::assertNull($info);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByPathAndMethodReturnsInfo(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByPathAndMethod('/pets', 'POST');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern());
        self::assertSame('post', $info->method());
        self::assertSame('createPet', $info->operationId());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByPathAndMethodReturnsNullForUnknown(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByPathAndMethod('/unknown', 'GET');

        self::assertNull($info);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testParametersAreMerged(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('getPetById');

        self::assertNotNull($info);
        self::assertNotEmpty($info->parameters());
        self::assertSame('petId', $info->parameters()[0]->name);
        self::assertSame('path', $info->parameters()[0]->in);
    }

    /**
     * @dataProvider providerOperationIds
     *
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    #[DataProvider('providerOperationIds')]
    public function testOperationIsIndexed(string $operationId): void
    {
        self::assertNotNull((new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId($operationId));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerOperationIds(): iterable
    {
        yield 'list pets' => ['listPets'];
        yield 'create pet' => ['createPet'];
        yield 'get pet' => ['getPetById'];
        yield 'update pet' => ['updatePet'];
        yield 'delete pet' => ['deletePet'];
        yield 'patch pet' => ['patchPet'];
        yield 'options pet' => ['optionsPet'];
        yield 'head pet' => ['headPet'];
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByPathAndMethodIsCaseInsensitive(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByPathAndMethod('/pets', 'get');

        self::assertNotNull($info);
        self::assertSame('listPets', $info->operationId());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByRequestPathAndMethodMatchesTemplatedPath(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByRequestPathAndMethod('/pets/123', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets/{petId}', $info->pathPattern());
        self::assertSame('getPetById', $info->operationId());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testFindByRequestPathAndMethodPrefersExactPath(): void
    {
        $info = (new OperationLookup(Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByRequestPathAndMethod('/pets', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern());
        self::assertSame('listPets', $info->operationId());
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
    public function testOperationDefinitionIncludesEffectiveServerUrls(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Operation Server API
              version: 1.0.0
            servers:
              - url: https://root.example.com
            paths:
              /pets:
                servers:
                  - url: https://path.example.com/v1
                get:
                  operationId: listPets
                  servers:
                    - url: https://operation.example.com/v2
                  responses:
                    '200':
                      description: OK
            YAML);

        $lookup = new OperationLookup($schema);
        $info = $lookup->findByOperationId('listPets');

        self::assertNotNull($info);
        self::assertSame(['https://operation.example.com/v2'], $info->serverUrls());
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     */
    public function testSchemaWithNoPaths(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Empty
              version: 1.0.0
            paths: {}
            YAML);

        $lookup = new OperationLookup($schema);
        self::assertNull($lookup->findByOperationId('anything'));
    }
}
