<?php

declare(strict_types=1);

namespace OasFake\Exception;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends OasFakeException
{
    private readonly ValidationFailed $validationError;

    private function __construct(string $message, ValidationFailed $validationError)
    {
        $this->validationError = $validationError;
        parent::__construct($message, 0, $validationError);
    }

    public static function forRequest(RequestInterface $request, ValidationFailed $previous): self
    {
        return new self(
            sprintf('Request validation failed for %s %s: %s', $request->getMethod(), (string) $request->getUri(), $previous->getMessage()),
            $previous,
        );
    }

    public static function forResponse(ResponseInterface $response, string $path, string $method, ValidationFailed $previous): self
    {
        return new self(
            sprintf('Response validation failed for %s %s (status %d): %s', $method, $path, $response->getStatusCode(), $previous->getMessage()),
            $previous,
        );
    }

    public function getValidationError(): ValidationFailed
    {
        return $this->validationError;
    }
}
