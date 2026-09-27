<?php

declare(strict_types=1);

namespace OasFake;

use function array_keys;
use function array_map;

use cebe\openapi\spec\Parameter;

use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;

use function range;

/**
 * Serializes generated parameter values according to OpenAPI styles.
 *
 * @visibility namespace
 */
final class ParameterSerializer
{
    /**
     * Serialize a query parameter, including exploded object keys.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     *
     * @return array<string, list<string>|string>
     */
    public function query(Parameter $parameter, mixed $value): array
    {
        $delimiter = match ($parameter->style) {
            'spaceDelimited' => ' ',
            'pipeDelimited' => '|',
            default => null,
        };

        return $delimiter === null
            ? (new FormQueryParameterSerializer())->serialize($parameter, $value, $this)
            : [$parameter->name => $this->delimitedValue($value, $delimiter, false)];
    }

    /**
     * Serialize a path or header parameter.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     */
    public function delimited(Parameter $parameter, mixed $value): string
    {
        $style = $parameter->style;
        $explode = $parameter->explode;

        if ($parameter->in === 'path' && $style === 'label') {
            return '.' . $this->delimitedValue($value, '.', $explode);
        }

        if ($parameter->in === 'path' && $style === 'matrix') {
            return $this->matrix($parameter->name, $value, $explode);
        }

        return $this->delimitedValue($value, ',', $explode);
    }

    /**
     * Serialize a matrix-style path parameter.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     */
    public function matrix(string $name, mixed $value, bool $explode): string
    {
        if (!is_array($value)) {
            return ';' . $name . '=' . $this->scalar($value);
        }

        if ($this->isList($value)) {
            if (!$explode) {
                return ';' . $name . '=' . $this->delimitedValue($value, ',', false);
            }

            return implode('', array_map(
                fn ($item): string => ';' . $name . '=' . $this->scalar($item),
                $value,
            ));
        }

        if (!$explode) {
            return ';' . $name . '=' . $this->delimitedValue($value, ',', false);
        }

        $segments = [];
        foreach ($value as $property => $propertyValue) {
            $segments[] = ';' . $property . '=' . $this->scalar($propertyValue);
        }

        return implode('', $segments);
    }

    /**
     * Serialize a scalar, list, or object with an explicit delimiter.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     */
    public function delimitedValue(mixed $value, string $delimiter, bool $explode): string
    {
        if (!is_array($value)) {
            return $this->scalar($value);
        }

        if ($this->isList($value)) {
            return implode($delimiter, array_map(
                fn ($item): string => $this->scalar($item),
                $value,
            ));
        }

        $parts = [];
        foreach ($value as $property => $propertyValue) {
            if ($explode) {
                $parts[] = $property . '=' . $this->scalar($propertyValue);
            } else {
                $parts[] = (string) $property;
                $parts[] = $this->scalar($propertyValue);
            }
        }

        return implode($delimiter, $parts);
    }

    /**
     * Convert a generated scalar-like value to its wire representation.
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @throws JsonException when a structured value cannot be encoded
     */
    public function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Distinguish sequential arrays from OpenAPI object values on PHP 8.0.
     *
     * @template TValue
     *
     * @param array<TValue> $value
     */
    public function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
