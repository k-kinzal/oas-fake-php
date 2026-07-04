<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OperationInfo;
use OasFake\OperationLookup;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationLookup::class)]
#[CoversClass(OperationInfo::class)]
final class OperationLookupTest extends TestCase
{
    private OperationLookup $lookup;

    protected function setUp(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $this->lookup = new OperationLookup($schema);
    }

    public function testFindByOperationIdReturnsInfo(): void
    {
        $info = $this->lookup->findByOperationId('listPets');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('get', $info->method);
        self::assertSame('listPets', $info->operationId);
    }

    public function testFindByOperationIdReturnsNullForUnknown(): void
    {
        $info = $this->lookup->findByOperationId('nonexistent');

        self::assertNull($info);
    }

    public function testFindByPathAndMethodReturnsInfo(): void
    {
        $info = $this->lookup->findByPathAndMethod('/pets', 'POST');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('post', $info->method);
        self::assertSame('createPet', $info->operationId);
    }

    public function testFindByPathAndMethodReturnsNullForUnknown(): void
    {
        $info = $this->lookup->findByPathAndMethod('/unknown', 'GET');

        self::assertNull($info);
    }

    public function testParametersAreMerged(): void
    {
        $info = $this->lookup->findByOperationId('getPetById');

        self::assertNotNull($info);
        self::assertNotEmpty($info->parameters);
        self::assertSame('petId', $info->parameters[0]->name);
        self::assertSame('path', $info->parameters[0]->in);
    }

    public function testAllOperationsIndexed(): void
    {
        $ids = ['listPets', 'createPet', 'getPetById', 'updatePet', 'deletePet', 'patchPet', 'optionsPet', 'headPet'];

        foreach ($ids as $id) {
            self::assertNotNull($this->lookup->findByOperationId($id), "Operation '$id' should be found");
        }
    }

    public function testFindByPathAndMethodIsCaseInsensitive(): void
    {
        $info = $this->lookup->findByPathAndMethod('/pets', 'get');

        self::assertNotNull($info);
        self::assertSame('listPets', $info->operationId);
    }

    public function testFindByRequestPathAndMethodMatchesTemplatedPath(): void
    {
        $info = $this->lookup->findByRequestPathAndMethod('/pets/123', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets/{petId}', $info->pathPattern);
        self::assertSame('getPetById', $info->operationId);
    }

    public function testFindByRequestPathAndMethodPrefersExactPath(): void
    {
        $info = $this->lookup->findByRequestPathAndMethod('/pets', 'GET');

        self::assertNotNull($info);
        self::assertSame('/pets', $info->pathPattern);
        self::assertSame('listPets', $info->operationId);
    }

    public function testOperationInfoIncludesEffectiveServerUrls(): void
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
        self::assertSame(['https://operation.example.com/v2'], $info->serverUrls);
    }

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
