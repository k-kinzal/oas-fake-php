<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Faker;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(Faker::class)]
final class FakerTest extends TestCase
{
    private Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
    }

    public function testResponseGeneratesValidJsonResponse(): void
    {
        $faker = new Faker($this->schema);
        $operation = new OperationAddress('/pets', 'get');

        $response = $faker->response($operation);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
    }

    public function testResponseForPathGeneratesResponse(): void
    {
        $faker = new Faker($this->schema);

        $response = $faker->responseForPath('/pets/{petId}', 'GET', 200);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function testResponseWithFakerOptions(): void
    {
        $faker = new Faker($this->schema, ['alwaysFakeOptionals' => true]);

        $response = $faker->responseForPath('/pets/{petId}', 'GET', 200);

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('tag', $data);
    }

    public function testResponseForPathWithCustomStatusCode(): void
    {
        $faker = new Faker($this->schema);

        $response = $faker->responseForPath('/pets/{petId}', 'GET', 404);

        self::assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('code', $data);
        self::assertArrayHasKey('message', $data);
    }
}
