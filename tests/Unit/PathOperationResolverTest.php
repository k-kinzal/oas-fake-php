<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\PathOperationResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class PathOperationResolverTest extends TestCase
{
    public function testResolveSelectsMethodSpecificOperation(): void
    {
        $paths = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')->openApi()->paths;
        self::assertNotNull($paths);
        $pathItem = $paths->getPath('/pets');
        self::assertNotNull($pathItem);

        $operation = (new PathOperationResolver())->resolve($pathItem, 'post');

        self::assertNotNull($operation);
        self::assertSame('createPet', $operation->operationId);
    }

    public function testResolveReturnsNullForUnsupportedMethod(): void
    {
        $paths = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')->openApi()->paths;
        self::assertNotNull($paths);
        $pathItem = $paths->getPath('/pets');
        self::assertNotNull($pathItem);

        self::assertNull((new PathOperationResolver())->resolve($pathItem, 'connect'));
    }
}
