<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;

/**
 * Value object holding resolved information about a single OpenAPI operation.
 */
final class OperationInfo
{
    /**
     * @param list<Parameter> $parameters
     */
    public function __construct(
        public string $pathPattern,
        public string $method,
        public string $operationId,
        public Operation $operation,
        public array $parameters,
    ) {
    }
}
