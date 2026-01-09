<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Validation;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use GuzzleHttp\Psr7\ServerRequest;
use OasFakePHP\Exception\RequestValidationException;
use OasFakePHP\Validation\RequestValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestValidator::class)]
final class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $schemaPath = __DIR__ . '/../../Fixtures/openapi/petstore.yaml';
        $schema = Reader::readFromYamlFile($schemaPath, OpenApi::class, true);
        $this->validator = new RequestValidator($schema);
    }

    public function testValidateReturnsOperationAddressOnSuccess(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        $operation = $this->validator->validate($request);

        self::assertSame('/pets', $operation->path());
        self::assertSame('get', $operation->method());
    }

    public function testValidateWithPathParameter(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets/123');

        $operation = $this->validator->validate($request);

        self::assertSame('/pets/{petId}', $operation->path());
    }

    public function testValidateThrowsOnInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid-path');

        $this->expectException(RequestValidationException::class);

        $this->validator->validate($request);
    }

    public function testIsValidReturnsTrueOnValidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        self::assertTrue($this->validator->isValid($request));
    }

    public function testIsValidReturnsFalseOnInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid');

        self::assertFalse($this->validator->isValid($request));
    }
}
