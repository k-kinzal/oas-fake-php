<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Response;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFakePHP\Response\FakeResponseGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeResponseGenerator::class)]
final class FakeResponseGeneratorTest extends TestCase
{
    private FakeResponseGenerator $generator;
    private OpenApi $schema;

    protected function setUp(): void
    {
        $schemaPath = __DIR__ . '/../../Fixtures/openapi/petstore.yaml';
        $this->schema = Reader::readFromYamlFile($schemaPath, OpenApi::class, true);
        $this->generator = new FakeResponseGenerator($this->schema);
    }

    public function testGenerateReturnsValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');

        $response = $this->generator->generate($operation);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testGenerateForPathReturnsValidResponse(): void
    {
        $response = $this->generator->generateForPath('/pets', 'GET');

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
    }

    public function testGenerateWithCustomStatusCode(): void
    {
        $operation = new OperationAddress('/pets/{petId}', 'get');

        $response = $this->generator->generate($operation, 200);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testGenerateWithFakerOptions(): void
    {
        $generator = new FakeResponseGenerator($this->schema, [
            'alwaysFakeOptionals' => true,
            'minItems' => 2,
            'maxItems' => 5,
        ]);

        $operation = new OperationAddress('/pets', 'get');
        $response = $generator->generate($operation);

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertGreaterThanOrEqual(2, count($body));
        self::assertLessThanOrEqual(5, count($body));
    }

    public function testGenerateProducesValidJsonBody(): void
    {
        $operation = new OperationAddress('/pets/{petId}', 'get');
        $response = $this->generator->generate($operation);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        self::assertNotNull($decoded);
        self::assertArrayHasKey('id', $decoded);
        self::assertArrayHasKey('name', $decoded);
    }
}
