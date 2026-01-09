<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Exception\OperationNotFoundException;
use OasFake\FakeRequest;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(FakeRequest::class)]
final class FakeRequestTest extends TestCase
{
    private Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
    }

    public function testForCreatesRequestForGetOperation(): void
    {
        $request = FakeRequest::for($this->schema, 'listPets');

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', $request->url());
        self::assertNull($request->body());
    }

    public function testForCreatesRequestWithPathParams(): void
    {
        $request = FakeRequest::for($this->schema, 'getPetById');

        self::assertSame('GET', $request->method());
        self::assertArrayHasKey('petId', $request->pathParams());
        // URL should have petId replaced
        self::assertStringNotContainsString('{petId}', $request->url());
        self::assertStringStartsWith('https://api.petstore.example.com/pets/', $request->url());
    }

    public function testForCreatesRequestWithBody(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');

        self::assertSame('POST', $request->method());
        self::assertNotNull($request->body());
        self::assertJson($request->body());

        $data = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('name', $data);
    }

    public function testForThrowsForUnknownOperation(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeRequest::for($this->schema, 'nonexistent');
    }

    public function testForPathCreatesRequest(): void
    {
        $request = FakeRequest::forPath($this->schema, '/pets', 'GET');

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', $request->url());
    }

    public function testForPathThrowsForUnknownPath(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeRequest::forPath($this->schema, '/nonexistent', 'GET');
    }

    public function testWithPathParamOverridesValue(): void
    {
        $request = FakeRequest::for($this->schema, 'getPetById');
        $modified = $request->withPathParam('petId', '99');

        self::assertSame('99', $modified->pathParams()['petId']);
        self::assertStringContainsString('/pets/99', $modified->url());
        // Original should be unchanged
        self::assertNotSame($request->pathParams()['petId'], '99');
    }

    public function testWithQueryParamAddsParam(): void
    {
        $request = FakeRequest::for($this->schema, 'listPets');
        $modified = $request->withQueryParam('limit', '10');

        self::assertSame('10', $modified->queryParams()['limit']);
        self::assertStringContainsString('limit=10', $modified->url());
    }

    public function testWithHeaderAddsHeader(): void
    {
        $request = FakeRequest::for($this->schema, 'listPets');
        $modified = $request->withHeader('Authorization', 'Bearer token');

        self::assertSame('Bearer token', $modified->headers()['Authorization']);
    }

    public function testWithBodyOverridesBody(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');
        $modified = $request->withBody('{"name":"custom"}');

        self::assertSame('{"name":"custom"}', $modified->body());
    }

    public function testToPsr7ReturnsServerRequestInterface(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');

        $psr7 = $request->toPsr7();

        self::assertInstanceOf(ServerRequestInterface::class, $psr7);
        self::assertSame('POST', $psr7->getMethod());
        self::assertStringStartsWith('https://api.petstore.example.com/pets', (string) $psr7->getUri());
    }

    public function testToCurlReturnsString(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');

        $curl = $request->toCurl();

        self::assertStringStartsWith('curl', $curl);
        self::assertStringContainsString('-X POST', $curl);
        self::assertStringContainsString('https://api.petstore.example.com/pets', $curl);
        self::assertStringContainsString("-d '", $curl);
    }

    public function testToCurlGetOmitsMethodFlag(): void
    {
        $request = FakeRequest::for($this->schema, 'listPets');

        $curl = $request->toCurl();

        self::assertStringNotContainsString('-X GET', $curl);
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');

        $array = $request->toArray();

        self::assertArrayHasKey('method', $array);
        self::assertArrayHasKey('url', $array);
        self::assertArrayHasKey('headers', $array);
        self::assertArrayHasKey('body', $array);
        self::assertSame('POST', $array['method']);
    }

    public function testHeadersIncludeContentTypeForPostRequest(): void
    {
        $request = FakeRequest::for($this->schema, 'createPet');

        self::assertArrayHasKey('Content-Type', $request->headers());
        self::assertSame('application/json', $request->headers()['Content-Type']);
    }

    public function testDeleteOperationHasNoBody(): void
    {
        $request = FakeRequest::for($this->schema, 'deletePet');

        self::assertSame('DELETE', $request->method());
        self::assertNull($request->body());
        self::assertArrayHasKey('petId', $request->pathParams());
    }
}
