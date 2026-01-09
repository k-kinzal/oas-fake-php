<?php

declare(strict_types=1);

namespace OasFakePHP\Exception;

final class SchemaNotFoundException extends OasFakeException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('OpenAPI schema file not found: %s', $path));
    }
}
