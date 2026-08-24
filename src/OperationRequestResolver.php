<?php

declare(strict_types=1);

namespace OasFake;

use function in_array;

use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves a PSR-7 request to its effective OpenAPI operation contract.
 */
final class OperationRequestResolver
{
    /**
     * Create a resolver for one server schema and validation policy.
     */
    public function __construct(
        private Schema $schema,
        private OperationLookup $operationLookup,
        private OperationPathResolver $pathResolver,
        private Validator $validator,
        private bool $validateRequests,
    ) {
    }

    /**
     * Resolve path, operation metadata, and validation address.
     *
     * @throws ValidationException when request validation is enabled and fails
     */
    public function resolve(ServerRequestInterface $request): OperationRequest
    {
        $resolvedPath = $this->pathResolver->resolveWithServerUrl($this->schema, $request);
        $path = $resolvedPath['path'];
        $method = $request->getMethod();
        $definition = $this->operationLookup->findByRequestPathAndMethod($path, $method);

        if ($definition !== null && $resolvedPath['serverUrl'] !== null && !in_array($resolvedPath['serverUrl'], $definition->serverUrls, true)) {
            $definition = null;
        }

        if ($this->validateRequests) {
            $address = $this->validator->validateRequest($request);
        } elseif ($definition !== null) {
            $address = new OperationAddress($definition->pathPattern, $definition->method);
        } else {
            $address = null;
        }

        return new OperationRequest($path, $method, $definition, $address);
    }
}
