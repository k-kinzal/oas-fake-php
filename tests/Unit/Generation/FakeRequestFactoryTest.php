<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\FakeRequestFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
final class FakeRequestFactoryTest extends TestCase
{
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
