<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\FakeRequestFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeRequestFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
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
        $context = new FakeDataContext(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('getPetById');

        self::assertNotNull($definition);
        $request = (new FakeRequestFactory())->create($context, $definition);

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets/', $request->url());
    }

    public function testCreateAddsRequestBodyMediaType(): void
    {
        $context = new FakeDataContext(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('createPet');

        self::assertNotNull($definition);
        $request = (new FakeRequestFactory())->create($context, $definition);

        self::assertNotNull($request->body());
        self::assertSame('application/json', $request->headers()['Content-Type']);
    }
}
