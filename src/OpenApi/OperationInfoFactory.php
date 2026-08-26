<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;

/**
 * Creates indexed operation metadata from one OpenAPI path operation.
 *
 * @visibility namespace
 */
final class OperationInfoFactory
{
    /**
     * @param list<Parameter> $pathParameters
     */
    public function create(
        Schema $schema,
        PathItem $pathItem,
        string $pathPattern,
        string $method,
        Operation $operation,
        array $pathParameters,
    ): OperationInfo {
        return new OperationInfo(
            pathPattern: $pathPattern,
            method: $method,
            operationId: $operation->operationId ?? '',
            operation: $operation,
            parameters: (new OperationParameterResolver())->merge($pathParameters, $operation),
            serverUrls: $schema->effectiveServerUrls($pathItem, $operation),
        );
    }
}
