<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Exception;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OpenAPIValidation\PSR7\Exception\Validation\RequiredParameterMissing;
use OasFakePHP\Exception\OasFakeException;
use OasFakePHP\Exception\RequestValidationException;
use OasFakePHP\Exception\ResponseValidationException;
use OasFakePHP\Exception\SchemaNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OasFakeException::class)]
#[CoversClass(SchemaNotFoundException::class)]
#[CoversClass(RequestValidationException::class)]
#[CoversClass(ResponseValidationException::class)]
final class ExceptionTest extends TestCase
{
    public function testSchemaNotFoundExceptionForPath(): void
    {
        $exception = SchemaNotFoundException::forPath('/path/to/schema.yaml');

        self::assertInstanceOf(OasFakeException::class, $exception);
        self::assertStringContainsString('/path/to/schema.yaml', $exception->getMessage());
    }

    public function testSchemaNotFoundExceptionWithMessage(): void
    {
        $exception = new SchemaNotFoundException('Custom message');

        self::assertSame('Custom message', $exception->getMessage());
    }

    public function testRequestValidationException(): void
    {
        $request = new Request('GET', 'https://example.com/pets');
        $validationError = RequiredParameterMissing::fromName('id');

        $exception = new RequestValidationException($request, $validationError);

        self::assertInstanceOf(OasFakeException::class, $exception);
        self::assertSame($request, $exception->getRequest());
        self::assertSame($validationError, $exception->getValidationError());
        self::assertStringContainsString('GET', $exception->getMessage());
        self::assertStringContainsString('https://example.com/pets', $exception->getMessage());
    }

    public function testResponseValidationException(): void
    {
        $response = new Response(200, [], '{}');
        $validationError = RequiredParameterMissing::fromName('name');

        $exception = new ResponseValidationException($response, $validationError, '/pets', 'GET');

        self::assertInstanceOf(OasFakeException::class, $exception);
        self::assertSame($response, $exception->getResponse());
        self::assertSame($validationError, $exception->getValidationError());
        self::assertSame('/pets', $exception->getPath());
        self::assertSame('GET', $exception->getMethod());
        self::assertStringContainsString('200', $exception->getMessage());
    }
}
