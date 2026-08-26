<?php

declare(strict_types=1);

namespace OasFake;

use function array_map;
use function array_values;

use cebe\openapi\spec\Parameter;

use function is_array;

use JsonException;

/**
 * Serializes deep-object and form-style OpenAPI query parameters.
 *
 * @visibility namespace
 */
final class FormQueryParameterSerializer
{
    /**
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     *
     * @return array<string, list<string>|string>
     */
    public function serialize(Parameter $parameter, mixed $value, ParameterSerializer $values): array
    {
        $name = $parameter->name;
        if ($parameter->style === 'deepObject' && is_array($value) && !$values->isList($value)) {
            $result = [];
            foreach ($value as $property => $propertyValue) {
                $result[$name . '[' . $property . ']'] = $values->scalar($propertyValue);
            }

            return $result;
        }

        if ($parameter->style !== 'form' || !is_array($value)) {
            return [$name => $values->scalar($value)];
        }

        if ($values->isList($value)) {
            return [$name => $parameter->explode
                ? array_map(fn ($item): string => $values->scalar($item), array_values($value))
                : $values->delimitedValue($value, ',', false)];
        }

        if (!$parameter->explode) {
            return [$name => $values->delimitedValue($value, ',', false)];
        }

        $result = [];
        foreach ($value as $property => $propertyValue) {
            $result[(string) $property] = $values->scalar($propertyValue);
        }

        return $result;
    }
}
