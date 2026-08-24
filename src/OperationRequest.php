<?php

declare(strict_types=1);

namespace OasFake;

use League\OpenAPIValidation\PSR7\OperationAddress;

/**
 * Carries the OpenAPI operation resolution for one incoming request.
 */
final class OperationRequest
{
    /**
     * Create a resolved operation request.
     */
    public function __construct(
        public string $path,
        public string $method,
        public ?OperationDefinition $definition,
        public ?OperationAddress $address,
    ) {
    }
}
