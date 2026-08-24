<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\Exception\OperationNotFoundException;
use OasFake\FakeDataContext;
use OasFake\FakeRequest;
use OasFake\Schema;
use OasFake\Testing\Petstore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationNotFoundException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContextResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeRequestFactory::class)]
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
final class FakeRequestTest extends TestCase
{
    public function testForCreatesRequestForGetOperation(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets');

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', $request->url());
        self::assertNull($request->body());
    }

    public function testForUsesOperationLevelServerUrl(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Operation Server API
              version: 1.0.0
            servers:
              - url: https://root.example.com
            paths:
              /pets:
                servers:
                  - url: https://path.example.com/v1
                get:
                  operationId: listPets
                  servers:
                    - url: https://operation.example.com/v2
                  responses:
                    '200':
                      description: OK
            YAML);

        $request = FakeRequest::for($schema, 'listPets');

        self::assertSame('https://operation.example.com/v2/pets', $request->url());
    }

    public function testForAcceptsFakeDataContext(): void
    {
        $request = FakeRequest::for(new FakeDataContext(Petstore::schema()), 'listPets');

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', $request->url());
    }

    public function testForCreatesRequestWithPathParams(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById');

        self::assertSame('GET', $request->method());
        self::assertArrayHasKey('petId', $request->pathParams());
        self::assertStringNotContainsString('{petId}', $request->url());
        self::assertStringStartsWith('https://api.petstore.example.com/pets/', $request->url());
    }

    /**
     * @throws JsonException when generated JSON cannot be decoded
     */
    public function testForCreatesRequestWithBody(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');

        self::assertSame('POST', $request->method());
        self::assertNotNull($request->body());
        self::assertJson($request->body());

        $data = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('name', $data);
    }

    public function testForUsesTextPlainRequestMediaType(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Text API
              version: 1.0.0
            paths:
              /messages:
                post:
                  operationId: createMessage
                  requestBody:
                    required: true
                    content:
                      text/plain:
                        schema:
                          type: string
                          enum: [hello]
                  responses:
                    '204':
                      description: Created
            YAML);

        $request = FakeRequest::for($schema, 'createMessage');

        self::assertSame('text/plain', $request->headers()['Content-Type']);
        self::assertSame('hello', $request->body());
    }

    public function testForUsesFormUrlEncodedRequestMediaType(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Form API
              version: 1.0.0
            paths:
              /messages:
                post:
                  operationId: createFormMessage
                  requestBody:
                    required: true
                    content:
                      application/x-www-form-urlencoded:
                        schema:
                          type: object
                          required: [message]
                          properties:
                            message:
                              type: string
                              enum: [hello]
                  responses:
                    '204':
                      description: Created
            YAML);

        $request = FakeRequest::for($schema, 'createFormMessage');

        self::assertSame('application/x-www-form-urlencoded', $request->headers()['Content-Type']);
        self::assertSame('message=hello', $request->body());
    }

    public function testForThrowsForUnknownOperation(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeRequest::for(Petstore::schema(), 'nonexistent');
    }

    public function testMethodReturnsUppercaseMethod(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets');

        self::assertSame('GET', $request->method());
    }

    public function testUrlReturnsFullUrl(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById')->withPathParam('petId', '123');

        self::assertSame('https://api.petstore.example.com/pets/123', $request->url());
    }

    public function testUrlEncodesPathParametersAsPathSegments(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById')->withPathParam('petId', "A/B pet's");

        self::assertSame('https://api.petstore.example.com/pets/A%2FB%20pet%27s', $request->url());
        self::assertSame('/pets/A%2FB%20pet%27s', $request->toPsr7()->getUri()->getPath());
    }

    public function testBodyReturnsRawBody(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet')->withBody('{"name":"Buddy"}');

        self::assertSame('{"name":"Buddy"}', $request->body());
    }

    public function testForPathCreatesRequest(): void
    {
        $request = FakeRequest::forPath(Petstore::schema(), '/pets', 'GET');

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', $request->url());
    }

    public function testForPathThrowsForUnknownPath(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeRequest::forPath(Petstore::schema(), '/nonexistent', 'GET');
    }

    public function testWithPathParamOverridesValue(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById');
        $modified = $request->withPathParam('petId', '99');

        self::assertSame('99', $modified->pathParams()['petId']);
        self::assertStringContainsString('/pets/99', $modified->url());
        self::assertNotSame($request->pathParams()['petId'], '99');
    }

    public function testPathParamsReturnsPathParameters(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById')->withPathParam('petId', '99');

        self::assertSame(['petId' => '99'], $request->pathParams());
    }

    public function testWithQueryParamAddsParam(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets');
        $modified = $request->withQueryParam('limit', '10');

        self::assertSame('10', $modified->queryParams()['limit']);
        self::assertStringContainsString('limit=10', $modified->url());
    }

    public function testUrlEncodesQueryParameterValues(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets')->withQueryParam('filter', 'friendly pets & cats');

        self::assertStringContainsString('filter=friendly%20pets%20%26%20cats', $request->url());
        self::assertSame('friendly pets & cats', $request->toPsr7()->getQueryParams()['filter']);
    }

    public function testQueryParamsReturnsQueryParameters(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets')->withQueryParam('limit', '10');

        self::assertSame(['limit' => '10'], $request->queryParams());
    }

    public function testUrlUsesRepeatedQueryParamsForGeneratedArrays(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets', [
            'alwaysFakeOptionals' => true,
            'minItems' => 2,
            'maxItems' => 2,
        ]);

        self::assertSame(['friendly', 'friendly'], $request->queryParams()['tags']);
        self::assertStringContainsString('tags=friendly&tags=friendly', $request->url());
        self::assertStringNotContainsString('tags%5B0%5D', $request->url());
        self::assertSame(['friendly', 'friendly'], $request->toPsr7()->getQueryParams()['tags']);
    }

    public function testWithHeaderAddsHeader(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets');
        $modified = $request->withHeader('Authorization', 'Bearer token');

        self::assertSame('Bearer token', $modified->headers()['Authorization']);
    }

    public function testWithBodyOverridesBody(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');
        $modified = $request->withBody('{"name":"custom"}');

        self::assertSame('{"name":"custom"}', $modified->body());
    }

    public function testToPsr7ReturnsServerRequestInterface(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');

        $psr7 = $request->toPsr7();

        self::assertSame('POST', $psr7->getMethod());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', (string) $psr7->getUri());
    }

    public function testToCurlReturnsString(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');

        $curl = $request->toCurl();

        self::assertStringStartsWith('curl', $curl);
        self::assertStringContainsString('-X POST', $curl);
        self::assertStringContainsString('https://api.petstore.example.com/pets', $curl);
        self::assertStringContainsString("-d '", $curl);
    }

    public function testToCurlShellQuotesSingleQuotes(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'getPetById')
            ->withPathParam('petId', "Bob's pet")
            ->withHeader('X-Owner', "O'Reilly")
            ->withBody('{"name":"Bob\'s pet"}');

        $curl = $request->toCurl();

        self::assertStringContainsString("'https://api.petstore.example.com/pets/Bob%27s%20pet'", $curl);
        self::assertStringContainsString("-H 'X-Owner: O'\\''Reilly'", $curl);
        self::assertStringContainsString("-d '{\"name\":\"Bob'\\''s pet\"}'", $curl);
    }

    public function testToCurlGetOmitsMethodFlag(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'listPets');

        $curl = $request->toCurl();

        self::assertStringNotContainsString('-X GET', $curl);
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');

        $array = $request->toArray();

        self::assertArrayHasKey('method', $array);
        self::assertArrayHasKey('url', $array);
        self::assertArrayHasKey('headers', $array);
        self::assertArrayHasKey('body', $array);
        self::assertSame('POST', $array['method']);
    }

    public function testHeadersIncludeContentTypeForPostRequest(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'createPet');

        self::assertArrayHasKey('Content-Type', $request->headers());
        self::assertSame('application/json', $request->headers()['Content-Type']);
    }

    public function testDeleteOperationHasNoBody(): void
    {
        $request = FakeRequest::for(Petstore::schema(), 'deletePet');

        self::assertSame('DELETE', $request->method());
        self::assertNull($request->body());
        self::assertArrayHasKey('petId', $request->pathParams());
    }
}
