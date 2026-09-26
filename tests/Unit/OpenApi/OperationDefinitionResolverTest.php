<?php

declare(strict_types=1);

namespace Tests\Unit;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use OasFake\OperationDefinitionResolver;
use OasFake\OperationIndexBuilder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationDefinitionResolver
 *
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationDefinition
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 */
#[CoversClass(OperationDefinitionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationDefinitionResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testResolveBindsAnOperationToItsPathAndMethod(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationIndexBuilder())->build($schema)['byOperationId']['getPetById'];

        self::assertSame('/pets/{petId}', $operation->pathPattern());
        self::assertSame('get', $operation->method());
        self::assertSame('getPetById', $operation->operationId());
    }

    /**
     * @throws TypeErrorException when the operation declarations are invalid
     */
    public function testResolveAppliesOperationOverridesToTheInheritedContract(): void
    {
        $schema = Schema::fromOpenApi(new OpenApi([
            'servers' => [['url' => 'https://root.example.com']],
        ]));
        $pathItem = new PathItem([
            'servers' => [['url' => 'https://path.example.com']],
        ]);
        $pathParameter = new Parameter(['name' => 'petId', 'in' => 'path', 'required' => true]);
        $pathLimit = new Parameter(['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer']]);
        $operation = new Operation([
            'operationId' => 'getPetById',
            'parameters' => [
                ['name' => 'limit', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                ['name' => 'limit', 'in' => 'header', 'schema' => ['type' => 'string']],
            ],
            'servers' => [['url' => 'https://operation.example.com']],
            'responses' => ['200' => ['description' => 'Pet']],
        ]);

        $definition = (new OperationDefinitionResolver())->resolve(
            $schema,
            $pathItem,
            '/pets/{petId}',
            'get',
            $operation,
            [$pathParameter, $pathLimit],
        );

        self::assertSame('getPetById', $definition->operationId());
        self::assertSame(['https://operation.example.com'], $definition->serverUrls());
        self::assertCount(3, $definition->parameters());
        self::assertSame($pathParameter, $definition->parameters()[0]);
        self::assertSame('query', $definition->parameters()[1]->in);
        self::assertTrue($definition->parameters()[1]->required);
        self::assertNotSame($pathLimit, $definition->parameters()[1]);
        self::assertSame('header', $definition->parameters()[2]->in);
    }

    /**
     * @throws TypeErrorException when the operation declarations are invalid
     */
    public function testResolveInheritsPathServersForAnUnnamedOperation(): void
    {
        $schema = Schema::fromOpenApi(new OpenApi([
            'servers' => [['url' => 'https://root.example.com']],
        ]));
        $pathItem = new PathItem([
            'servers' => [['url' => 'https://path.example.com']],
        ]);

        $definition = (new OperationDefinitionResolver())->resolve(
            $schema,
            $pathItem,
            '/health',
            'get',
            new Operation(['responses' => []]),
            [],
        );

        self::assertSame('', $definition->operationId());
        self::assertSame('/health', $definition->pathPattern());
        self::assertSame('get', $definition->method());
        self::assertSame(['https://path.example.com'], $definition->serverUrls());
        self::assertSame([], $definition->parameters());
    }
}
