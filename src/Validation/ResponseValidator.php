<?php

declare(strict_types=1);

namespace OasFakePHP\Validation;

use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator as LeagueResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use OasFakePHP\Exception\ResponseValidationException;
use Psr\Http\Message\ResponseInterface;

final class ResponseValidator
{
    private readonly LeagueResponseValidator $validator;

    public function __construct(OpenApi $schema)
    {
        $this->validator = (new ValidatorBuilder())
            ->fromSchema($schema)
            ->getResponseValidator();
    }

    /**
     * @throws ResponseValidationException
     */
    public function validate(OperationAddress $operation, ResponseInterface $response): void
    {
        try {
            $this->validator->validate($operation, $response);
        } catch (ValidationFailed $e) {
            throw new ResponseValidationException(
                $response,
                $e,
                $operation->path(),
                $operation->method(),
            );
        }
    }

    public function isValid(OperationAddress $operation, ResponseInterface $response): bool
    {
        try {
            $this->validator->validate($operation, $response);

            return true;
        } catch (ValidationFailed) {
            return false;
        }
    }
}
