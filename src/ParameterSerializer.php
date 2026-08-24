<?php

declare(strict_types=1);

namespace OasFake;

use function array_keys;
use function array_map;
use function array_values;

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
     * @throws JsonException when a structured value cannot be encoded
     *
     * @return array<string, list<string>|string>
     */
    public function query(Parameter $parameter, mixed $value): array
    {
        $style = $parameter->style;
        $explode = (bool) $parameter->explode;
        $name = $parameter->name;

        if ($style === 'spaceDelimited') {
            return [$name => $this->delimitedValue($value, ' ', false)];
        }

        if ($style === 'pipeDelimited') {
            return [$name => $this->delimitedValue($value, '|', false)];
        }

        if ($style === 'deepObject' && is_array($value) && !$this->isList($value)) {
            $result = [];
            foreach ($value as $property => $propertyValue) {
                $result[$name . '[' . (string) $property . ']'] = $this->scalar($propertyValue);
            }

            return $result;
        }

        if ($style !== 'form' || !is_array($value)) {
            return [$name => $this->scalar($value)];
        }

        if ($this->isList($value)) {
            return [
                $name => $explode
                    ? array_map(fn (mixed $item): string => $this->scalar($item), array_values($value))
                    : $this->delimitedValue($value, ',', false),
            ];
        }

        if (!$explode) {
            return [$name => $this->delimitedValue($value, ',', false)];
        }

        $result = [];
        foreach ($value as $property => $propertyValue) {
            $result[(string) $property] = $this->scalar($propertyValue);
        }

        return $result;
    }

    /**
     * Serialize a path or header parameter.
     *
     * @throws JsonException when a structured value cannot be encoded
     */
    public function delimited(Parameter $parameter, mixed $value): string
    {
        $style = $parameter->style;
        $explode = (bool) $parameter->explode;

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
                fn (mixed $item): string => ';' . $name . '=' . $this->scalar($item),
                array_values($value),
            ));
        }

        if (!$explode) {
            return ';' . $name . '=' . $this->delimitedValue($value, ',', false);
        }

        $segments = [];
        foreach ($value as $property => $propertyValue) {
            $segments[] = ';' . (string) $property . '=' . $this->scalar($propertyValue);
        }

        return implode('', $segments);
    }

    /**
     * Serialize a scalar, list, or object with an explicit delimiter.
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
                fn (mixed $item): string => $this->scalar($item),
                array_values($value),
            ));
        }

        $parts = [];
        foreach ($value as $property => $propertyValue) {
            if ($explode) {
                $parts[] = (string) $property . '=' . $this->scalar($propertyValue);
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
     * @param array<mixed> $value
     */
    public function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
