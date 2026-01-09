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
    public function __construct(
        public string $method,
        public string $path,
    ) {
    }
}
