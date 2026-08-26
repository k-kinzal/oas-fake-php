<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Exception\ValidationException;
use OasFake\Testing\Petstore;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\Validator
 *
 * @uses \OasFake\Exception\ValidationException
 * @uses \OasFake\Schema
 */
#[CoversClass(Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ValidationException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
final class ValidatorTest extends TestCase
{
    public function testValidateRequestReturnsOperationAddress(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        $operation = Petstore::validator()->validateRequest($request);

        self::assertSame('/pets', $operation->path());
        self::assertSame('get', $operation->method());
    }

    public function testValidateRequestThrowsValidationExceptionOnInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid-path');

        $this->expectException(ValidationException::class);

        Petstore::validator()->validateRequest($request);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     */
    public function testValidateResponseWithValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        Petstore::validator()->validateResponse($operation, $response);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     */
    public function testValidateResponseThrowsValidationExceptionOnInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $this->expectException(ValidationException::class);

        Petstore::validator()->validateResponse($operation, $response);
    }

    public function testIsValidRequestReturnsTrueForValidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        self::assertTrue(Petstore::validator()->isValidRequest($request));
    }

    public function testIsValidRequestReturnsFalseForInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid');

        self::assertFalse(Petstore::validator()->isValidRequest($request));
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     */
    public function testIsValidResponseReturnsTrueForValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertTrue(Petstore::validator()->isValidResponse($operation, $response));
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     */
    public function testIsValidResponseReturnsFalseForInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertFalse(Petstore::validator()->isValidResponse($operation, $response));
    }
}
