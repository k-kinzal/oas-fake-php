<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Callback
{
    public function __construct(
        public readonly string $path,
        public readonly string $method,
    ) {
    }
}
