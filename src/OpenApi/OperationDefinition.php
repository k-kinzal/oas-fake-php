<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;

/**
 * Binds an OpenAPI operation to its path, method, and resolved inheritance context.
 *
 * Routing identity cannot be changed after indexing. Request and response schemas
 * remain the parsed OpenAPI objects, without exposing the entire source operation.
 *
 * @visibility public
 *
 * @example Looking up the effective operation definition
 *     $schema = \OasFake\Schema::fromString('{"openapi":"3.0.0","info":{"title":"Pets","version":"1"},"paths":{"/pets":{"get":{"operationId":"listPets","responses":{"200":{"description":"ok"}}}}}}', 'json');
 *     $definition = (new \OasFake\OperationLookup($schema))->findByOperationId('listPets');
 *     $definition->pathPattern() // => '/pets'
 *     $definition->method() // => 'get'
 */
final class OperationDefinition
{
    private string $operationId;

    private ?RequestBody $requestBody;

    /** @var array<int|string, Reference|Response|null> */
    private array $responses;

    /**
     * Capture the operation's identity and effective path-level inheritance.
     *
     * @param list<Parameter> $parameters
     * @param list<string> $serverUrls
     */
    public function __construct(
        private string $pathPattern,
        private string $method,
        Operation $operation,
        private array $parameters,
        private array $serverUrls = ['/'],
    ) {
        $this->operationId = $operation->operationId ?? '';
        $requestBody = $operation->requestBody;
        $this->requestBody = $requestBody instanceof RequestBody ? $requestBody : null;
        $responses = $operation->responses;
        $this->responses = $responses instanceof Responses ? $responses->getResponses() : [];
    }

    /**
     * Return the schema path template used to identify this operation.
     */
    public function pathPattern(): string
    {
        return $this->pathPattern;
    }

    /**
     * Return the HTTP method paired with the schema path.
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Return the declared operation identifier, or an empty string when omitted.
     */
    public function operationId(): string
    {
        return $this->operationId;
    }

    /**
     * Return path parameters with operation-level overrides already applied.
     *
     * @return list<Parameter>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Return the server URLs selected by OpenAPI inheritance.
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        return $this->serverUrls;
    }

    /**
     * Return the resolved request body declaration, if present.
     */
    public function requestBody(): ?RequestBody
    {
        return $this->requestBody;
    }

    /**
     * Return response declarations keyed by HTTP status or default.
     *
     * @return array<int|string, Reference|Response|null>
     */
    public function responses(): array
    {
        return $this->responses;
    }
}
