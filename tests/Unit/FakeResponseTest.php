<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Exception\OperationNotFoundException;
use OasFake\FakeResponse;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(FakeResponse::class)]
final class FakeResponseTest extends TestCase
{
    private Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
    }

    public function testForGeneratesResponse(): void
    {
        $response = FakeResponse::for($this->schema, 'listPets');

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json', $response->headers()['Content-Type']);

        $data = $response->json();
        self::assertIsArray($data);
    }

    public function testForWithCustomStatusCode(): void
    {
        $response = FakeResponse::for($this->schema, 'getPetById', 404);

        self::assertSame(404, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('code', $data);
        self::assertArrayHasKey('message', $data);
    }

    public function testForPathGeneratesResponse(): void
    {
        $response = FakeResponse::forPath($this->schema, '/pets/{petId}', 'GET', 200);

        self::assertSame(200, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function testForThrowsForUnknownOperation(): void
    {
        $this->expectException(OperationNotFoundException::class);

        FakeResponse::for($this->schema, 'nonexistent');
    }

    public function testToPsr7ReturnsResponseInterface(): void
    {
        $response = FakeResponse::for($this->schema, 'listPets');

        $psr7 = $response->toPsr7();

        self::assertInstanceOf(ResponseInterface::class, $psr7);
        self::assertSame(200, $psr7->getStatusCode());
        self::assertSame('application/json', $psr7->getHeaderLine('Content-Type'));
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $response = FakeResponse::for($this->schema, 'listPets');

        $array = $response->toArray();

        self::assertArrayHasKey('statusCode', $array);
        self::assertArrayHasKey('headers', $array);
        self::assertArrayHasKey('body', $array);
        self::assertSame(200, $array['statusCode']);
    }

    public function testBodyReturnsRawJson(): void
    {
        $response = FakeResponse::for($this->schema, 'listPets');

        $body = $response->body();

        self::assertIsString($body);
        self::assertJson($body);
    }

    public function testWithFakerOptionsAlwaysFakeOptionals(): void
    {
        $response = FakeResponse::for($this->schema, 'getPetById', 200, ['alwaysFakeOptionals' => true]);

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('tag', $data);
    }

    public function testCreatePetReturnsCreatedStatus(): void
    {
        $response = FakeResponse::for($this->schema, 'createPet', 201);

        self::assertSame(201, $response->statusCode());

        $data = $response->json();
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function testGenerateResponseStaticMethod(): void
    {
        $psr7 = FakeResponse::generateResponse($this->schema, '/pets', 'GET', 200);

        self::assertInstanceOf(ResponseInterface::class, $psr7);
        self::assertSame(200, $psr7->getStatusCode());
    }
}
