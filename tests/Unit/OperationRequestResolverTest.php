<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\OperationLookup;
use OasFake\OperationPathResolver;
use OasFake\OperationRequestResolver;
use OasFake\Schema;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Validator::class)]
final class OperationRequestResolverTest extends TestCase
{
    public function testResolveUsesEffectiveServerPathAndOperation(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $resolver = new OperationRequestResolver(
            $schema,
            new OperationLookup($schema),
            new OperationPathResolver(),
            new Validator($schema),
            false,
        );

        $resolved = $resolver->resolve(new ServerRequest('GET', 'https://api.petstore.example.com/pets'));

        self::assertSame('/pets', $resolved->path);
        self::assertSame('listPets', $resolved->definition?->operationId);
        self::assertSame('/pets', $resolved->address?->path());
    }
}
