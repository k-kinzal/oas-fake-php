<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Exception\ValidationException;
use OasFake\Schema;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    private Validator $validator;

    private Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $this->validator = new Validator($this->schema);
    }

    public function testValidateRequestReturnsOperationAddress(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        $operation = $this->validator->validateRequest($request);

        self::assertInstanceOf(OperationAddress::class, $operation);
        self::assertSame('/pets', $operation->path());
        self::assertSame('get', $operation->method());
    }

    public function testValidateRequestThrowsValidationExceptionOnInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid-path');

        $this->expectException(ValidationException::class);

        $this->validator->validateRequest($request);
    }

    public function testValidateResponseWithValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $this->validator->validateResponse($operation, $response);

        $this->addToAssertionCount(1);
    }

    public function testValidateResponseThrowsValidationExceptionOnInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $this->expectException(ValidationException::class);

        $this->validator->validateResponse($operation, $response);
    }

    public function testIsValidRequestReturnsTrueForValidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        self::assertTrue($this->validator->isValidRequest($request));
    }

    public function testIsValidRequestReturnsFalseForInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid');

        self::assertFalse($this->validator->isValidRequest($request));
    }

    public function testIsValidResponseReturnsTrueForValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertTrue($this->validator->isValidResponse($operation, $response));
    }

    public function testIsValidResponseReturnsFalseForInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertFalse($this->validator->isValidResponse($operation, $response));
    }
}
