<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\FakeDataContext;
use OasFake\RequestBodyGenerator;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestBodyGenerator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class RequestBodyGeneratorTest extends TestCase
{
    /**
     * @throws JsonException when generated data cannot be serialized
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the fixture request is missing
     */
    public function testGenerateUsesDeclaredRequestMediaType(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('createPet');
        self::assertNotNull($definition);

        $payload = (new RequestBodyGenerator())->generate($context, $definition);

        self::assertSame('application/json', $payload['mediaType']);
        self::assertNotNull($payload['body']);
    }

    /**
     * @throws JsonException when generated data cannot be serialized
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the fixture request is missing
     */
    public function testGenerateUsesTheSelectedNonJsonSchema(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'JSON'
            {
                "openapi": "3.0.0",
                "info": {"title": "Form API", "version": "1.0.0"},
                "paths": {
                    "/submit": {
                        "post": {
                            "operationId": "submitForm",
                            "requestBody": {
                                "required": true,
                                "content": {
                                    "application/x-www-form-urlencoded": {
                                        "schema": {
                                            "type": "object",
                                            "required": ["name"],
                                            "properties": {"name": {"type": "string"}}
                                        }
                                    }
                                }
                            },
                            "responses": {"200": {"description": "Submitted"}}
                        }
                    }
                }
            }
            JSON, 'json');
        $context = new FakeDataContext($schema);
        $definition = $context->operationLookup()->findByOperationId('submitForm');
        self::assertNotNull($definition);

        $payload = (new RequestBodyGenerator())->generate($context, $definition);

        self::assertSame('application/x-www-form-urlencoded', $payload['mediaType']);
        self::assertMatchesRegularExpression('/^name=.+$/', $payload['body'] ?? '');
    }

    /**
     * @throws JsonException when generated data cannot be serialized
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the fixture path is missing
     * @throws \Vural\OpenAPIFaker\Exception\NoRequest when the operation has no request body
     */
    public function testGenerateRejectsAnOperationWithoutARequestBody(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('listPets');
        self::assertNotNull($definition);

        $this->expectException(\Vural\OpenAPIFaker\Exception\NoRequest::class);

        (new RequestBodyGenerator())->generate($context, $definition);
    }
}
