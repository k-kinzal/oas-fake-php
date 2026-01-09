<?php

declare(strict_types=1);

namespace OasFake\Exception;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when request or response validation against the OpenAPI schema fails.
 */
class ValidationException extends OasFakeException
{
    private ValidationFailed $validationError;

    private function __construct(string $message, ValidationFailed $validationError)
    {
        $this->validationError = $validationError;
        parent::__construct($message, 0, $validationError);
    }

    /**
     * Create an exception for a failed request validation.
     *
     * @param RequestInterface $request The invalid request
     * @param ValidationFailed $previous The underlying validation error
     */
    public static function forRequest(RequestInterface $request, ValidationFailed $previous): self
    {
        return new self(
            sprintf('Request validation failed for %s %s: %s', $request->getMethod(), (string) $request->getUri(), $previous->getMessage()),
            $previous,
        );
    }

    /**
     * Create an exception for a failed response validation.
     *
     * @param ResponseInterface $response The invalid response
     * @param string $path The OpenAPI path
     * @param string $method The HTTP method
     * @param ValidationFailed $previous The underlying validation error
     */
    public static function forResponse(ResponseInterface $response, string $path, string $method, ValidationFailed $previous): self
    {
        return new self(
            sprintf('Response validation failed for %s %s (status %d): %s', $method, $path, $response->getStatusCode(), $previous->getMessage()),
            $previous,
        );
    }

    /**
     * Return the underlying validation error.
     */
    public function getValidationError(): ValidationFailed
    {
        return $this->validationError;
    }
}
