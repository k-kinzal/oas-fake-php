<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use OasFake\OperationParameterResolver;
use OasFake\PathOperationResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OperationParameterResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testForPathReturnsDeclaredParameters(): void
    {
        $parameter = new Parameter(['name' => 'petId', 'in' => 'path']);
        $pathItem = new PathItem(['parameters' => [$parameter]]);

        self::assertSame([$parameter], (new OperationParameterResolver())->forPath($pathItem));
    }

    public function testForPathReturnsOnlyPathLevelParameters(): void
    {
        $paths = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')->openApi()->paths;
        self::assertNotNull($paths);
        $pathItem = $paths->getPath('/pets/{petId}');
        self::assertNotNull($pathItem);

        $parameters = (new OperationParameterResolver())->forPath($pathItem);

        self::assertSame([], $parameters);
    }

    public function testMergeRetainsEffectivePathParameters(): void
    {
        $paths = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')->openApi()->paths;
        self::assertNotNull($paths);
        $pathItem = $paths->getPath('/pets/{petId}');
        self::assertNotNull($pathItem);
        $operation = (new PathOperationResolver())->resolve($pathItem, 'get');
        self::assertNotNull($operation);
        $resolver = new OperationParameterResolver();

        $parameters = $resolver->merge($resolver->forPath($pathItem), $operation);

        self::assertCount(1, $parameters);
        self::assertSame('petId', $parameters[0]->name);
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testMergeUsesOperationParametersAsOverrides(): void
    {
        $pathParameter = new Parameter(['name' => 'petId', 'in' => 'path', 'description' => 'path default']);
        $operationParameter = new Parameter(['name' => 'petId', 'in' => 'path', 'description' => 'operation override']);
        $queryParameter = new Parameter(['name' => 'expand', 'in' => 'query']);
        $operation = new Operation(['responses' => [], 'parameters' => [$operationParameter, $queryParameter]]);

        $parameters = (new OperationParameterResolver())->merge([$pathParameter], $operation);

        self::assertSame([$operationParameter, $queryParameter], $parameters);
    }
}
