<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Operation;
use OasFake\OperationInfo;
use OasFake\OperationLookup;
use OasFake\OperationResponseResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationResponseResolver
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class OperationResponseResolverTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testDefaultsWhenNoSuccessfulResponseIsDeclared(): void
    {
        $definition = new OperationInfo('/pets', 'get', 'listPets', new Operation(['responses' => []]), []);
        $resolver = new OperationResponseResolver();

        self::assertSame(200, $resolver->defaultStatusCode($definition));
        self::assertTrue($resolver->hasSuccessfulBody($definition));
        self::assertNull($resolver->forStatus($definition, 404));
        self::assertSame('application/json', $resolver->mediaType($definition, 404));
        self::assertNull($resolver->schema($definition, 404, 'application/json'));
    }

    public function testDefaultStatusCodeReturnsFirstSuccessfulResponse(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertSame(201, (new OperationResponseResolver())->defaultStatusCode($definition));
    }

    public function testSuccessfulResponse(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        $successful = (new OperationResponseResolver())->successfulResponse($definition);

        self::assertSame(201, $successful['status'] ?? null);
        self::assertSame('Pet created', $successful['response']->description ?? null);
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testSuccessfulResponseHonorsThe2xxRange(): void
    {
        $definition = new OperationInfo('/pets', 'get', 'listPets', new Operation([
            'responses' => [
                '199' => ['description' => 'Informational'],
                '200' => [
                    'description' => 'Successful',
                    'content' => ['text/plain' => ['schema' => ['type' => 'string']]],
                ],
                '300' => ['description' => 'Redirect'],
            ],
        ]), []);
        $resolver = new OperationResponseResolver();

        $successful = $resolver->successfulResponse($definition);

        self::assertSame(200, $successful['status'] ?? null);
        self::assertSame('Successful', $successful['response']->description ?? null);
        self::assertTrue($resolver->hasSuccessfulBody($definition));
        self::assertSame('text/plain', $resolver->mediaType($definition, 200));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testSuccessfulResponseRejectsThe300Boundary(): void
    {
        $definition = new OperationInfo('/pets', 'get', 'listPets', new Operation([
            'responses' => ['300' => ['description' => 'Redirect']],
        ]), []);

        self::assertNull((new OperationResponseResolver())->successfulResponse($definition));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testForStatusRejectsAnUnresolvedReference(): void
    {
        $definition = new OperationInfo('/pets', 'get', 'listPets', new Operation([
            'responses' => ['201' => ['$ref' => '#/components/responses/Created']],
        ]), []);
        $resolver = new OperationResponseResolver();

        self::assertNull($resolver->forStatus($definition, 201));
        self::assertNull($resolver->successfulResponse($definition));
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testForStatusReturnsNullWithoutAResponsesCollection(): void
    {
        $definition = new OperationInfo('/pets', 'get', 'listPets', new Operation([]), []);
        $resolver = new OperationResponseResolver();

        self::assertNull($resolver->forStatus($definition, 200));
        self::assertNull($resolver->successfulResponse($definition));
    }

    public function testHasSuccessfulBodyReflectsResponseContent(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('deletePet');
        self::assertNotNull($definition);

        self::assertFalse((new OperationResponseResolver())->hasSuccessfulBody($definition));
    }

    public function testForStatusReturnsExactResponse(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('createPet');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->forStatus($definition, 201));
    }

    public function testMediaTypePrefersJson(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertSame('application/json', (new OperationResponseResolver())->mediaType($definition, 200));
    }

    public function testSchemaReturnsMediaTypeSchema(): void
    {
        $definition = (new OperationLookup(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->findByOperationId('listPets');
        self::assertNotNull($definition);

        self::assertNotNull((new OperationResponseResolver())->schema($definition, 200, 'application/json'));
        self::assertNull((new OperationResponseResolver())->schema($definition, 200, 'text/plain'));
    }
}
