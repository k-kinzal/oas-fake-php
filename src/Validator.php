<?php

declare(strict_types=1);

namespace OasFake;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator as LeagueResponseValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator as LeagueServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use OasFake\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates PSR-7 requests and responses against an OpenAPI schema.
 */
final class Validator
{
    private LeagueServerRequestValidator $requestValidator;

    private LeagueResponseValidator $responseValidator;

    public function __construct(Schema $schema)
    {
        $builder = (new ValidatorBuilder())->fromSchema($schema->openApi());
        $this->requestValidator = $builder->getServerRequestValidator();
        $this->responseValidator = $builder->getResponseValidator();
    }

    /**
     * @throws ValidationException
     */
    public function validateRequest(ServerRequestInterface $request): OperationAddress
    {
        try {
            return $this->requestValidator->validate($request);
        } catch (ValidationFailed $e) {
            throw ValidationException::forRequest($request, $e);
        }
    }

    /**
     * @throws ValidationException
     */
    public function validateResponse(OperationAddress $operation, ResponseInterface $response): void
    {
        try {
            $this->responseValidator->validate($operation, $response);
        } catch (ValidationFailed $e) {
            throw ValidationException::forResponse($response, $operation->path(), $operation->method(), $e);
        }
    }

    /**
     * Check whether a request is valid without throwing an exception.
     *
     * @param ServerRequestInterface $request The request to validate
     *
     * @return bool True if the request conforms to the OpenAPI schema
     */
    public function isValidRequest(ServerRequestInterface $request): bool
    {
        try {
            $this->requestValidator->validate($request);

            return true;
        } catch (ValidationFailed) {
            return false;
        }
    }

    /**
     * Check whether a response is valid without throwing an exception.
     *
     * @param OperationAddress $operation The operation the response belongs to
     * @param ResponseInterface $response The response to validate
     *
     * @return bool True if the response conforms to the OpenAPI schema
     */
    public function isValidResponse(OperationAddress $operation, ResponseInterface $response): bool
    {
        try {
            $this->responseValidator->validate($operation, $response);

            return true;
        } catch (ValidationFailed) {
            return false;
        }
    }
}
