<?php

declare(strict_types=1);

namespace OasFakePHP\Exception;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Psr\Http\Message\ResponseInterface;

final class ResponseValidationException extends OasFakeException
{
    public function __construct(
        private readonly ResponseInterface $response,
        private readonly ValidationFailed $validationError,
        private readonly string $path = '',
        private readonly string $method = '',
    ) {
        parent::__construct(
            sprintf(
                'Response validation failed for %s %s (status %d): %s',
                $this->method,
                $this->path,
                $this->response->getStatusCode(),
                $this->validationError->getMessage(),
            ),
            0,
            $this->validationError,
        );
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getValidationError(): ValidationFailed
    {
        return $this->validationError;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
