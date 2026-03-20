<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use OasFake\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationException::class)]
final class ValidationExceptionTest extends TestCase
{
    public function testForRequestCreatesCorrectMessage(): void
    {
        $request = new Request('GET', 'https://example.com/pets');
        $validationError = new ValidationFailed('Invalid parameter');

        $exception = ValidationException::forRequest($request, $validationError);

        self::assertStringContainsString('Request validation failed', $exception->getMessage());
        self::assertStringContainsString('GET', $exception->getMessage());
        self::assertStringContainsString('https://example.com/pets', $exception->getMessage());
    }

    public function testForResponseCreatesCorrectMessage(): void
    {
        $response = new Response(400);
        $validationError = new ValidationFailed('Invalid body');

        $exception = ValidationException::forResponse($response, '/pets', 'POST', $validationError);

        self::assertStringContainsString('Response validation failed', $exception->getMessage());
        self::assertStringContainsString('POST', $exception->getMessage());
        self::assertStringContainsString('/pets', $exception->getMessage());
        self::assertStringContainsString('400', $exception->getMessage());
    }

    public function testGetValidationErrorReturnsOriginal(): void
    {
        $request = new Request('GET', 'https://example.com/pets');
        $validationError = new ValidationFailed('Test error');

        $exception = ValidationException::forRequest($request, $validationError);

        self::assertSame($validationError, $exception->getValidationError());
    }

    public function testPreviousExceptionIsSet(): void
    {
        $request = new Request('DELETE', 'https://example.com/pets/1');
        $validationError = new ValidationFailed('Missing field');

        $exception = ValidationException::forRequest($request, $validationError);

        self::assertSame($validationError, $exception->getPrevious());
    }
}
