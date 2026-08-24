<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles a PSR-7 request through OpenAPI resolution and response validation.
 */
final class SchemaRequestHandler implements RequestHandlerInterface
{
    /**
     * Create the terminal request handler for an interceptor pipeline.
     */
    public function __construct(
        private OperationRequestResolver $operationResolver,
        private OperationResponder $operationResponder,
        private Validator $validator,
        private bool $validateResponses,
    ) {
    }

    /**
     * Generate and validate a response for the incoming request.
     *
     * @throws ValidationException when enabled request or response validation fails
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $operationRequest = $this->operationResolver->resolve($request);
        $response = $this->operationResponder->respond(
            $request,
            $operationRequest->path,
            $operationRequest->method,
            $operationRequest->definition,
        );
        $this->validateResponse($operationRequest, $response);

        return $response;
    }

    /**
     * Validate a response against a previously resolved operation address.
     *
     * @throws ValidationException when response validation is enabled and fails
     */
    public function validateResponse(OperationRequest $operationRequest, ResponseInterface $response): void
    {
        if ($this->validateResponses && $operationRequest->address !== null) {
            $this->validator->validateResponse($operationRequest->address, $response);
        }
    }
}
