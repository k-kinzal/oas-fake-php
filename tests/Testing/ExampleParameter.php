<?php

declare(strict_types=1);

namespace OasFake\Testing;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Parameter;

/**
 * Builds OpenAPI parameter examples with explicit serialization policies.
 */
final class ExampleParameter
{
    /**
     * Build a required array parameter whose generated values are deterministic.
     *
     * @throws TypeErrorException when the OpenAPI parameter definition is invalid
     */
    public static function array(string $name, string $in, string $style, bool $explode): Parameter
    {
        return new Parameter([
            'name' => $name,
            'in' => $in,
            'required' => true,
            'style' => $style,
            'explode' => $explode,
            'schema' => [
                'type' => 'array',
                'minItems' => 2,
                'maxItems' => 2,
                'items' => [
                    'type' => 'string',
                    'enum' => ['friendly'],
                ],
            ],
        ]);
    }
}
