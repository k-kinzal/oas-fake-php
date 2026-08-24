<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\PathItem;
use OasFake\PathOperationResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class PathOperationResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when path metadata is invalid
     *
     * @dataProvider providerMethods
     */
    #[DataProvider('providerMethods')]
    public function testResolveSelectsMethodSpecificOperation(string $method): void
    {
        $pathItem = new PathItem([
            $method => [
                'operationId' => $method . 'Pet',
                'responses' => ['200' => ['description' => 'Successful']],
            ],
        ]);

        self::assertSame($method . 'Pet', (new PathOperationResolver())->resolve($pathItem, $method)?->operationId);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerMethods(): iterable
    {
        yield 'get' => ['get'];
        yield 'post' => ['post'];
        yield 'put' => ['put'];
        yield 'delete' => ['delete'];
        yield 'patch' => ['patch'];
        yield 'options' => ['options'];
        yield 'head' => ['head'];
        yield 'trace' => ['trace'];
    }

    public function testResolveReturnsNullForUnsupportedMethod(): void
    {
        $paths = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')->openApi()->paths;
        self::assertNotNull($paths);
        $pathItem = $paths->getPath('/pets');
        self::assertNotNull($pathItem);

        self::assertNull((new PathOperationResolver())->resolve($pathItem, 'connect'));
    }
}
