<?php

declare(strict_types=1);

namespace OasFakePHP\Validation;

use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ServerRequestValidator as LeagueServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use OasFakePHP\Exception\RequestValidationException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestValidator
{
    private readonly LeagueServerRequestValidator $validator;

    public function __construct(OpenApi $schema)
    {
        $this->validator = (new ValidatorBuilder())
            ->fromSchema($schema)
            ->getServerRequestValidator();
    }

    /**
     * @throws RequestValidationException
     */
    public function validate(ServerRequestInterface $request): OperationAddress
    {
        try {
            return $this->validator->validate($request);
        } catch (ValidationFailed $e) {
            throw new RequestValidationException($request, $e);
        }
    }

    public function isValid(ServerRequestInterface $request): bool
    {
        try {
            $this->validator->validate($request);

            return true;
        } catch (ValidationFailed) {
            return false;
        }
    }
}
