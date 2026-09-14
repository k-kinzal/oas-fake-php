<?php

declare(strict_types=1);

namespace Tests\Unit;

use JsonException;
use OasFake\ParameterFaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\ParameterFaker
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\ParameterSerializer
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 * @uses \OasFake\FormQueryParameterSerializer
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(ParameterFaker::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ParameterSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FormQueryParameterSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class ParameterFakerTest extends TestCase
{
    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testGeneratesPathParameter(): void
    {
        $info = (new \OasFake\OperationLookup(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('getPetById');
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
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testSkipsOptionalParametersWhenNotAlwaysFakeOptionals(): void
    {
        $info = (new \OasFake\OperationLookup(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertSame([], $result['query']);
    }

    /**
     * @throws JsonException when a generated parameter cannot be serialized
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testGeneratesOptionalParametersWhenAlwaysFakeOptionals(): void
    {
        $info = (new \OasFake\OperationLookup(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
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
            new \cebe\openapi\spec\Parameter([
                'name' => 'tags',
                'in' => 'query',
                'required' => true,
                'style' => 'form',
                'explode' => true,
                'schema' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 2,
                    'items' => [
                        'type' => 'string',
                        'enum' => ['friendly'],
                    ],
                ],
            ]),
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
            new \cebe\openapi\spec\Parameter([
                'name' => 'tags',
                'in' => 'query',
                'required' => true,
                'style' => 'pipeDelimited',
                'explode' => false,
                'schema' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 2,
                    'items' => [
                        'type' => 'string',
                        'enum' => ['friendly'],
                    ],
                ],
            ]),
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
            new \cebe\openapi\spec\Parameter([
                'name' => 'X-Tags',
                'in' => 'header',
                'required' => true,
                'style' => 'simple',
                'explode' => false,
                'schema' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 2,
                    'items' => [
                        'type' => 'string',
                        'enum' => ['friendly'],
                    ],
                ],
            ]),
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
            new \cebe\openapi\spec\Parameter([
                'name' => 'tags',
                'in' => 'path',
                'required' => true,
                'style' => 'matrix',
                'explode' => true,
                'schema' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 2,
                    'items' => [
                        'type' => 'string',
                        'enum' => ['friendly'],
                    ],
                ],
            ]),
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
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testValuesAreStrings(): void
    {
        $info = (new \OasFake\OperationLookup(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('getPetById');
        self::assertNotNull($info);

        $faker = new ParameterFaker();
        $result = $faker->generate($info->parameters);

        self::assertMatchesRegularExpression('/^.+$/', $result['path']['petId']);
    }
}
