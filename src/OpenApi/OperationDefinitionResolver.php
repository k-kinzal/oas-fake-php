<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;

/**
 * Resolves inherited parameters and server URLs for one schema path operation.
 *
 * @visibility namespace
 */
final class OperationDefinitionResolver
{
    /**
     * Bind the operation to its effective path and operation-level declarations.
     *
     * @param list<Parameter> $pathParameters
     */
    public function resolve(
        Schema $schema,
        PathItem $pathItem,
        string $pathPattern,
        string $method,
        Operation $operation,
        array $pathParameters,
    ): OperationDefinition {
        return new OperationDefinition(
            pathPattern: $pathPattern,
            method: $method,
            operation: $operation,
            parameters: (new OperationParameterResolver())->merge($pathParameters, $operation),
            serverUrls: $schema->effectiveServerUrls($pathItem, $operation),
        );
    }
}
