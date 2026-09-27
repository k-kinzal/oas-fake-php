<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Exception\ValidationException;
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
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testValidateRequestReturnsOperationAddress(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        $operation = (new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->validateRequest($request);

        self::assertSame('/pets', $operation->path());
        self::assertSame('get', $operation->method());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testValidateRequestThrowsValidationExceptionOnInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid-path');

        $this->expectException(ValidationException::class);

        (new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->validateRequest($request);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testValidateResponseWithValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        (new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->validateResponse($operation, $response);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testValidateResponseThrowsValidationExceptionOnInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $this->expectException(ValidationException::class);

        (new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->validateResponse($operation, $response);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testIsValidRequestReturnsTrueForValidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/pets');

        self::assertTrue((new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->isValidRequest($request));
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testIsValidRequestReturnsFalseForInvalidRequest(): void
    {
        $request = new ServerRequest('GET', 'https://api.petstore.example.com/invalid');

        self::assertFalse((new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->isValidRequest($request));
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testIsValidResponseReturnsTrueForValidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode([['id' => 1, 'name' => 'Fido']], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertTrue((new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->isValidResponse($operation, $response));
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testIsValidResponseReturnsFalseForInvalidResponse(): void
    {
        $operation = new OperationAddress('/pets', 'get');
        $body = json_encode(['invalid' => 'data'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        self::assertFalse((new Validator(\OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')))->isValidResponse($operation, $response));
    }
}
