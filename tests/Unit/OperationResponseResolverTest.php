<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\OperationLookup;
use OasFake\OperationResponseResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationResponseResolver::class)]
final class OperationResponseResolverTest extends TestCase
{
    public function testDefaultStatusCodeReturnsFirstSuccessfulResponse(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertSame(201, (new OperationResponseResolver())->defaultStatusCode($definition));
    }

    public function testHasSuccessfulBodyReflectsResponseContent(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('deletePet');
        self::assertNotNull($definition);

        self::assertFalse((new OperationResponseResolver())->hasSuccessfulBody($definition));
    }

    public function testForStatusReturnsExactResponse(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->forStatus($definition, 201));
    }

    public function testMediaTypePrefersJson(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertSame('application/json', (new OperationResponseResolver())->mediaType($definition, 200));
    }

    public function testSchemaReturnsMediaTypeSchema(): void
    {
        $definition = (new OperationLookup(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->schema($definition, 200, 'application/json'));
    }
}
