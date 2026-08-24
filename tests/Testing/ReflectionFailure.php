<?php

declare(strict_types=1);

namespace OasFake\Testing;

use ReflectionException;

/**
 * Supplies the checked failure type emitted by PHP's Reflection API.
 */
final class ReflectionFailure
{
    /**
     * Return a representative attribute-instantiation failure.
     */
    public static function attribute(): ReflectionException
    {
        return new ReflectionException('attribute failed');
    }
}
