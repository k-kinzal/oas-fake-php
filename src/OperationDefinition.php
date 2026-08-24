<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;

/**
 * Describes one indexed OpenAPI operation and its effective routing metadata.
 */
final class OperationDefinition
{
    /**
     * @param list<Parameter> $parameters
     * @param list<string> $serverUrls
     */
    public function __construct(
        public string $pathPattern,
        public string $method,
        public string $operationId,
        public Operation $operation,
        public array $parameters,
        public array $serverUrls = ['/'],
    ) {
    }
}
