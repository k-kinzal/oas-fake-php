<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use JsonException;
use OasFake\Exception\OperationNotFoundException;
use OasFake\FakeDataContext;
use OasFake\FakeResponse;
use OasFake\Schema;
use OasFake\Testing\Petstore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationNotFoundException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContextResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class FakeResponseTest extends TestCase
{
    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testForGeneratesResponse(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json', $response->headers()['Content-Type']);

        $data = $response->json();
        self::assertIsArray($data);
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testForAcceptsFakeDataContext(): void
    {
        $response = FakeResponse::for(new FakeDataContext(Petstore::schema()), 'listPets');

        self::assertSame(200, $response->statusCode());
        self::assertIsArray($response->json());
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testForWithCustomStatusCode(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'getPetById', 404);

        self::assertSame(404, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('code', $data);
        self::assertArrayHasKey('message', $data);
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testForPathGeneratesResponse(): void
    {
        $response = FakeResponse::forPath(Petstore::schema(), '/pets/{petId}', 'GET', 200);

        self::assertSame(200, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function testForThrowsForUnknownOperation(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeResponse::for(Petstore::schema(), 'nonexistent');
    }

    public function testStatusCodeReturnsHttpStatus(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'createPet', 201);

        self::assertSame(201, $response->statusCode());
    }

    public function testForDefaultsToFirstSuccessStatusCode(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'createPet');

        self::assertSame(201, $response->statusCode());
    }

    public function testForPathDefaultsToFirstSuccessStatusCode(): void
    {
        $response = FakeResponse::forPath(Petstore::schema(), '/pets', 'POST');

        self::assertSame(201, $response->statusCode());
    }

    public function testHeadersReturnsResponseHeaders(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        self::assertSame('application/json', $response->headers()['Content-Type']);
    }

    public function testForUsesTextPlainResponseMediaType(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Text API
              version: 1.0.0
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

        $response = FakeResponse::for($schema, 'getStatus');

        self::assertSame('text/plain', $response->headers()['Content-Type']);
        self::assertSame('ok', $response->body());
    }

    public function testForUsesFormUrlEncodedResponseMediaType(): void
    {
        $schema = Schema::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Form API
              version: 1.0.0
            paths:
              /status:
                get:
                  operationId: getStatusForm
                  responses:
                    '200':
                      description: Status
                      content:
                        application/x-www-form-urlencoded:
                          schema:
                            type: object
                            required: [status]
                            properties:
                              status:
                                type: string
                                enum: [ok]
            YAML);

        $response = FakeResponse::for($schema, 'getStatusForm');

        self::assertSame('application/x-www-form-urlencoded', $response->headers()['Content-Type']);
        self::assertSame('status=ok', $response->body());
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testJsonDecodesResponseBody(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        self::assertIsArray($response->json());
    }

    public function testToPsr7ReturnsResponseInterface(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        $psr7 = $response->toPsr7();

        self::assertSame(200, $psr7->getStatusCode());
        self::assertSame('application/json', $psr7->getHeaderLine('Content-Type'));
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        $array = $response->toArray();

        self::assertArrayHasKey('statusCode', $array);
        self::assertArrayHasKey('headers', $array);
        self::assertArrayHasKey('body', $array);
        self::assertSame(200, $array['statusCode']);
    }

    public function testBodyReturnsRawJson(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'listPets');

        $body = $response->body();

        self::assertJson($body);
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testWithFakerOptionsAlwaysFakeOptionals(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'getPetById', 200, ['alwaysFakeOptionals' => true]);

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('tag', $data);
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testCreatePetReturnsCreatedStatus(): void
    {
        $response = FakeResponse::for(Petstore::schema(), 'createPet', 201);

        self::assertSame(201, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function testGenerateResponseStaticMethod(): void
    {
        $psr7 = FakeResponse::generateResponse(Petstore::schema(), '/pets', 'GET', 200);

        self::assertSame(200, $psr7->getStatusCode());
    }

    public function testGenerateResponseAcceptsFakeDataContext(): void
    {
        $psr7 = FakeResponse::generateResponse(new FakeDataContext(Petstore::schema()), '/pets', 'GET', 200);

        self::assertSame(200, $psr7->getStatusCode());
    }

    public function testFromPsr7PreservesStatusHeadersAndBody(): void
    {
        $response = FakeResponse::fromPsr7(new Response(202, ['X-Test' => ['one', 'two']], 'accepted'));

        self::assertSame(202, $response->statusCode());
        self::assertSame('one, two', $response->headers()['X-Test']);
        self::assertSame('accepted', $response->body());
    }
}
