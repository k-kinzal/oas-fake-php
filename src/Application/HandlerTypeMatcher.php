<?php

declare(strict_types=1);

namespace OasFake;

use function is_a;

use ReflectionNamedType;
use ReflectionType;

/**
 * Matches reflected handler types against the PSR contracts they must satisfy.
 *
 * @visibility namespace
 */
final class HandlerTypeMatcher
{
    /**
     * Check whether a reflected named type accepts the expected contract.
     */
    public function allows(?ReflectionType $type, string $expected): bool
    {
        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_a($type->getName(), $expected, true);
    }

    /**
     * Check whether a reflected named type accepts null and the expected contract.
     */
    public function allowsNullable(?ReflectionType $type, string $expected): bool
    {
        return $type instanceof ReflectionNamedType
            && $type->allowsNull()
            && $this->allows($type, $expected);
    }
}
