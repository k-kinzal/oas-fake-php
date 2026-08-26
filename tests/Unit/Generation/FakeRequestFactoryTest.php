<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\FakeDataContext;
use OasFake\FakeRequestFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\FakeRequestFactory
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\ParameterFaker
 * @uses \OasFake\ParameterSerializer
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\RequestBodyGenerator
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(FakeRequestFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ParameterFaker::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ParameterSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\RequestBodyGenerator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class FakeRequestFactoryTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the exercised contract propagates it
     */
    public function testCreateBuildsRequestFromIndexedOperation(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('getPetById');

        self::assertNotNull($definition);
        $request = (new FakeRequestFactory())->create($context, $definition);

        self::assertSame('get', $request['method']);
        self::assertSame('https://api.petstore.example.com', $request['baseUrl']);
        self::assertSame('/pets/{petId}', $request['pathPattern']);
        self::assertArrayHasKey('petId', $request['pathParams']);
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the exercised contract propagates it
     */
    public function testCreateAddsRequestBodyMediaType(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('createPet');

        self::assertNotNull($definition);
        $request = (new FakeRequestFactory())->create($context, $definition);

        self::assertNotNull($request['rawBody']);
        self::assertSame('application/json', $request['headerParams']['Content-Type']);
    }
}
