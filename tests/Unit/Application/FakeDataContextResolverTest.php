<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\FakeDataContextResolver;
use OasFake\Schema;
use OasFake\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\FakeDataContextResolver
 *
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\Server
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 * @uses \OasFake\ServerRuntime
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\ServerMiddleware
 */
#[CoversClass(FakeDataContextResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
final class FakeDataContextResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testResolvePreservesContextAndBuildsFromSchema(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);
        $resolver = new FakeDataContextResolver();

        self::assertSame($context, $resolver->resolve($context));
        self::assertSame($schema, $resolver->resolve($schema)->schema());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testResolveUsesServerSchemaAndAllowsExplicitOptionOverrides(): void
    {
        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withFakerOptions(['minItems' => 1]);
        $resolver = new FakeDataContextResolver();

        self::assertSame(['minItems' => 1], $resolver->resolve($server)->fakerOptions());
        self::assertSame(['maxItems' => 2], $resolver->resolve($server, ['maxItems' => 2])->fakerOptions());
    }
}
