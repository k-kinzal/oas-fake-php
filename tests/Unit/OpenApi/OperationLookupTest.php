<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OperationLookup;
use OasFake\Schema;
use OasFake\Testing\Petstore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\RequestPathMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationLookupTest extends TestCase
{
    public function testFindByOperationIdReturnsInfo(): void
    {
        $info = Petstore::lookup()->findByOperationId('listPets');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('get', $info->method);
        self::assertSame('listPets', $info->operationId);
    }

    public function testFindByOperationIdReturnsNullForUnknown(): void
    {
        $info = Petstore::lookup()->findByOperationId('nonexistent');

        self::assertNull($info);
    }

    public function testFindByPathAndMethodReturnsInfo(): void
    {
        $info = Petstore::lookup()->findByPathAndMethod('/pets', 'POST');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('post', $info->method);
        self::assertSame('createPet', $info->operationId);
    }

    public function testFindByPathAndMethodReturnsNullForUnknown(): void
    {
        $info = Petstore::lookup()->findByPathAndMethod('/unknown', 'GET');

        self::assertNull($info);
    }

    public function testParametersAreMerged(): void
    {
        $info = Petstore::lookup()->findByOperationId('getPetById');

        self::assertNotNull($info);
        self::assertNotEmpty($info->parameters);
        self::assertSame('petId', $info->parameters[0]->name);
        self::assertSame('path', $info->parameters[0]->in);
    }

    /**
     * @dataProvider providerOperationIds
     */
    #[DataProvider('providerOperationIds')]
    public function testOperationIsIndexed(string $operationId): void
    {
        self::assertNotNull(Petstore::lookup()->findByOperationId($operationId));
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

    public function testFindByPathAndMethodIsCaseInsensitive(): void
    {
        $info = Petstore::lookup()->findByPathAndMethod('/pets', 'get');

        self::assertNotNull($info);
        self::assertSame('listPets', $info->operationId);
    }

    public function testFindByRequestPathAndMethodMatchesTemplatedPath(): void
    {
        $info = Petstore::lookup()->findByRequestPathAndMethod('/pets/123', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets/{petId}', $info->pathPattern);
        self::assertSame('getPetById', $info->operationId);
    }

    public function testFindByRequestPathAndMethodPrefersExactPath(): void
    {
        $info = Petstore::lookup()->findByRequestPathAndMethod('/pets', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('listPets', $info->operationId);
    }

    public function testOperationInfoIncludesEffectiveServerUrls(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
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
        self::assertSame(['https://operation.example.com/v2'], $info->serverUrls);
    }

    public function testSchemaWithNoPaths(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
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
