<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\FakeDataContextResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeDataContextResolver::class)]
final class FakeDataContextResolverTest extends TestCase
{
    public function testResolvePreservesContextAndBuildsFromSchema(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $context = new FakeDataContext($schema);
        $resolver = new FakeDataContextResolver();

        self::assertSame($context, $resolver->resolve($context));
        self::assertSame($schema, $resolver->resolve($schema)->schema());
    }
}
