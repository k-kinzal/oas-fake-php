<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OperationLookup;
use OasFake\ParameterFaker;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterFaker::class)]
final class ParameterFakerTest extends TestCase
{
    private Schema $schema;

    private OperationLookup $lookup;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $this->lookup = new OperationLookup($this->schema);
    }

    public function testGeneratesPathParameter(): void
    {
        $info = $this->lookup->findByOperationId('getPetById');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertArrayHasKey('path', $result);
        self::assertArrayHasKey('query', $result);
        self::assertArrayHasKey('header', $result);
        self::assertArrayHasKey('petId', $result['path']);
        self::assertNotSame('', $result['path']['petId']);
    }

    public function testSkipsOptionalParametersWhenNotAlwaysFakeOptionals(): void
    {
        $info = $this->lookup->findByOperationId('listPets');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        // "limit" is optional, so should be skipped
        self::assertSame([], $result['query']);
    }

    public function testGeneratesOptionalParametersWhenAlwaysFakeOptionals(): void
    {
        $info = $this->lookup->findByOperationId('listPets');
        self::assertNotNull($info);

        $faker = new ParameterFaker(['alwaysFakeOptionals' => true]);
        $result = $faker->generate($info->parameters);

        self::assertArrayHasKey('limit', $result['query']);
    }

    public function testGeneratesEmptyForNoParameters(): void
    {
        $faker = new ParameterFaker();
        $result = $faker->generate([]);

        self::assertSame([], $result['path']);
        self::assertSame([], $result['query']);
        self::assertSame([], $result['header']);
    }

    public function testValuesAreStrings(): void
    {
        $info = $this->lookup->findByOperationId('getPetById');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        foreach ($result['path'] as $value) {
            self::assertIsString($value);
        }
    }
}
