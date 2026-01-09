<?php

declare(strict_types=1);

namespace OasFakePHP\Exception;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Psr\Http\Message\RequestInterface;

final class RequestValidationException extends OasFakeException
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ValidationFailed $validationError,
    ) {
        parent::__construct(
            sprintf(
                'Request validation failed for %s %s: %s',
                $this->request->getMethod(),
                (string) $this->request->getUri(),
                $this->validationError->getMessage(),
            ),
            0,
            $this->validationError,
        );
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getValidationError(): ValidationFailed
    {
        return $this->validationError;
    }
}
