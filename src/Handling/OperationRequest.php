<?php

declare(strict_types=1);

namespace OasFake;

use League\OpenAPIValidation\PSR7\OperationAddress;

/**
 * Carries the OpenAPI operation resolution for one incoming request.
 *
 * @visibility namespace
 */
final class OperationRequest
{
    /**
     * Create a resolved operation request.
     */
    public function __construct(
        public string $path,
        public string $method,
        public ?OperationInfo $definition,
        public ?OperationAddress $address,
    ) {
    }
}
