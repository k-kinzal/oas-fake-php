<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\ParameterFaker;
use OasFake\Testing\ExampleParameter;
use OasFake\Testing\Petstore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterFaker::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ParameterSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
final class ParameterFakerTest extends TestCase
{
    /**
     * @throws JsonException when a generated parameter cannot be serialized
     */
    public function testGeneratesPathParameter(): void
    {
        $info = Petstore::lookup()->findByOperationId('getPetById');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertArrayHasKey('path', $result);
        self::assertArrayHasKey('query', $result);
        self::assertArrayHasKey('header', $result);
        self::assertArrayHasKey('petId', $result['path']);
        self::assertNotSame('', $result['path']['petId']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     */
    public function testSkipsOptionalParametersWhenNotAlwaysFakeOptionals(): void
    {
        $info = Petstore::lookup()->findByOperationId('listPets');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertSame([], $result['query']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     */
    public function testGeneratesOptionalParametersWhenAlwaysFakeOptionals(): void
    {
        $info = Petstore::lookup()->findByOperationId('listPets');
        self::assertNotNull($info);

        $faker = new ParameterFaker(['alwaysFakeOptionals' => true]);
        $result = $faker->generate($info->parameters);

        self::assertArrayHasKey('limit', $result['query']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when the fixture definition is invalid
     */
    public function testGeneratesFormExplodedQueryArray(): void
    {
        $faker = new ParameterFaker(['minItems' => 2, 'maxItems' => 2]);
        $result = $faker->generate([
            ExampleParameter::array('tags', 'query', 'form', true),
        ]);

        self::assertSame(['friendly', 'friendly'], $result['query']['tags']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when the fixture definition is invalid
     */
    public function testGeneratesPipeDelimitedQueryArray(): void
    {
        $faker = new ParameterFaker(['minItems' => 2, 'maxItems' => 2]);
        $result = $faker->generate([
            ExampleParameter::array('tags', 'query', 'pipeDelimited', false),
        ]);

        self::assertSame('friendly|friendly', $result['query']['tags']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when the fixture definition is invalid
     */
    public function testGeneratesSimpleHeaderArray(): void
    {
        $faker = new ParameterFaker(['minItems' => 2, 'maxItems' => 2]);
        $result = $faker->generate([
            ExampleParameter::array('X-Tags', 'header', 'simple', false),
        ]);

        self::assertSame('friendly,friendly', $result['header']['X-Tags']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when the fixture definition is invalid
     */
    public function testGeneratesMatrixPathArray(): void
    {
        $faker = new ParameterFaker(['minItems' => 2, 'maxItems' => 2]);
        $result = $faker->generate([
            ExampleParameter::array('tags', 'path', 'matrix', true),
        ]);

        self::assertSame(';tags=friendly;tags=friendly', $result['path']['tags']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     */
    public function testGeneratesEmptyForNoParameters(): void
    {
        $faker = new ParameterFaker();
        $result = $faker->generate([]);

        self::assertSame([], $result['path']);
        self::assertSame([], $result['query']);
        self::assertSame([], $result['header']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     */
    public function testValuesAreStrings(): void
    {
        $info = Petstore::lookup()->findByOperationId('getPetById');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertIsString($result['path']['petId']);
    }
}
