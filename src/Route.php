<?php

declare(strict_types=1);

namespace OasFake;

use Attribute;

/**
 * PHP attribute for mapping server methods to specific HTTP routes.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Route
{
    /**
     * @param string $method HTTP method matched by the handler
     * @param string $path OpenAPI path template matched by the handler
     */
    public function __construct(
        public string $method,
        public string $path,
    ) {
    }
}
