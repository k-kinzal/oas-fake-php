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
