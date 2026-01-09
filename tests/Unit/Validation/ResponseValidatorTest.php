<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Validation;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use GuzzleHttp\Psr7\Response;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFakePHP\Exception\ResponseValidationException;
use OasFakePHP\Validation\ResponseValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseValidator::class)]
final class ResponseValidatorTest extends TestCase
{
    private ResponseValidator $validator;

    protected function setUp(): void
    {
        $schemaPath = __DIR__ . '/../../Fixtures/openapi/petstore.yaml';
        $schema = Reader::readFromYamlFile($schemaPath, OpenApi::class, true);
        $this->validator = new ResponseValidator($schema);
    }

    public function testValidateSucceedsWithValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'Fluffy']], JSON_THROW_ON_ERROR),
        );

        // Should not throw
        $this->validator->validate($operation, $response);
        self::assertTrue(true);
    }

    public function testValidateThrowsOnInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['invalid' => 'data']], JSON_THROW_ON_ERROR),
        );

        $this->expectException(ResponseValidationException::class);

        $this->validator->validate($operation, $response);
    }

    public function testIsValidReturnsTrueOnValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'Test']], JSON_THROW_ON_ERROR),
        );

        self::assertTrue($this->validator->isValid($operation, $response));
    }

    public function testIsValidReturnsFalseOnInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"invalid": "structure"}',
        );

        self::assertFalse($this->validator->isValid($operation, $response));
    }
}
