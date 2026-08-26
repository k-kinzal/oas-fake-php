<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\FakeDataContext;
use OasFake\FakeResponseFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vural\OpenAPIFaker\Exception\NoPath;

/**
 * @covers \OasFake\FakeResponseFactory
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\OperationResponseResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\Schema
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class FakeResponseFactoryTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testCreateUsesDeclaredStatusMediaTypeAndSchema(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));
        $response = (new FakeResponseFactory())->create($context, '/pets', 'get', 200);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertJson((string) $response->getBody());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testCreatePreservesFakerFailureContract(): void
    {
        $context = new FakeDataContext(\OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml'));

        $this->expectException(NoPath::class);
        (new FakeResponseFactory())->create($context, '/unknown', 'get', 200);
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testCreateGeneratesNonJsonPayloadFromTheDeclaredSchema(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info: {title: Text API, version: 1.0.0}
            paths:
              /status:
                get:
                  operationId: getStatus
                  responses:
                    '200':
                      description: Status
                      content:
                        text/plain:
                          schema:
                            type: string
                            enum: [ok]
            YAML);

        $response = (new FakeResponseFactory())->create(new FakeDataContext($schema), '/status', 'get', 200);

        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertSame('ok', (string) $response->getBody());
    }
}
