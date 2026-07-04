<?php

declare(strict_types=1);

namespace OasFake;

use function array_keys;
use function array_map;
use function array_values;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema as CebeSchema;

use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use function range;

use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

/**
 * Generates fake values for OpenAPI path, query, and header parameters.
 */
final class ParameterFaker
{
    private Options $options;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     */
    public function __construct(array $fakerOptions = [])
    {
        $this->options = new Options();

        if (isset($fakerOptions['alwaysFakeOptionals'])) {
            $this->options->setAlwaysFakeOptionals($fakerOptions['alwaysFakeOptionals']);
        }

        if (isset($fakerOptions['minItems'])) {
            $this->options->setMinItems($fakerOptions['minItems']);
        }

        if (isset($fakerOptions['maxItems'])) {
            $this->options->setMaxItems($fakerOptions['maxItems']);
        }
    }

    /**
     * @param list<Parameter> $parameters
     *
     * @return array{path: array<string, string>, query: array<string, list<string>|string>, header: array<string, string>}
     */
    public function generate(array $parameters): array
    {
        $result = ['path' => [], 'query' => [], 'header' => []];

        foreach ($parameters as $parameter) {
            $in = $parameter->in;
            if ($in !== 'path' && $in !== 'query' && $in !== 'header') {
                continue;
            }

            if (!$parameter->required && !$this->options->getAlwaysFakeOptionals()) {
                continue;
            }

            $schema = $parameter->schema;
            if (!$schema instanceof CebeSchema) {
                continue;
            }

            $value = (new SchemaFaker($schema, $this->options))->generate();

            if ($in === 'query') {
                foreach ($this->serializeQueryParameter($parameter, $value) as $name => $serializedValue) {
                    $result['query'][$name] = $serializedValue;
                }
            } else {
                $result[$in][$parameter->name] = $this->serializeDelimitedParameter($parameter, $value);
            }
        }

        return $result;
    }

    /**
     * @return array<string, list<string>|string>
     */
    private function serializeQueryParameter(Parameter $parameter, mixed $value): array
    {
        $style = $parameter->style;
        $explode = (bool) $parameter->explode;
        $name = $parameter->name;

        if ($style === 'spaceDelimited') {
            return [$name => $this->serializeDelimitedValue($value, ' ', false)];
        }

        if ($style === 'pipeDelimited') {
            return [$name => $this->serializeDelimitedValue($value, '|', false)];
        }

        if ($style === 'deepObject' && is_array($value) && !$this->isList($value)) {
            $result = [];
            foreach ($value as $property => $propertyValue) {
                $result[$name . '[' . (string) $property . ']'] = $this->scalarToString($propertyValue);
            }

            return $result;
        }

        if ($style !== 'form' || !is_array($value)) {
            return [$name => $this->scalarToString($value)];
        }

        if ($this->isList($value)) {
            return [
                $name => $explode
                    ? array_map(fn (mixed $item): string => $this->scalarToString($item), array_values($value))
                    : $this->serializeDelimitedValue($value, ',', false),
            ];
        }

        if (!$explode) {
            return [$name => $this->serializeDelimitedValue($value, ',', false)];
        }

        $result = [];
        foreach ($value as $property => $propertyValue) {
            $result[(string) $property] = $this->scalarToString($propertyValue);
        }

        return $result;
    }

    private function serializeDelimitedParameter(Parameter $parameter, mixed $value): string
    {
        $style = $parameter->style;
        $explode = (bool) $parameter->explode;

        if ($parameter->in === 'path' && $style === 'label') {
            return '.' . $this->serializeDelimitedValue($value, '.', $explode);
        }

        if ($parameter->in === 'path' && $style === 'matrix') {
            return $this->serializeMatrixValue($parameter->name, $value, $explode);
        }

        return $this->serializeDelimitedValue($value, ',', $explode);
    }

    private function serializeMatrixValue(string $name, mixed $value, bool $explode): string
    {
        if (!is_array($value)) {
            return ';' . $name . '=' . $this->scalarToString($value);
        }

        if ($this->isList($value)) {
            if (!$explode) {
                return ';' . $name . '=' . $this->serializeDelimitedValue($value, ',', false);
            }

            return implode('', array_map(
                fn (mixed $item): string => ';' . $name . '=' . $this->scalarToString($item),
                array_values($value),
            ));
        }

        if (!$explode) {
            return ';' . $name . '=' . $this->serializeDelimitedValue($value, ',', false);
        }

        $segments = [];
        foreach ($value as $property => $propertyValue) {
            $segments[] = ';' . (string) $property . '=' . $this->scalarToString($propertyValue);
        }

        return implode('', $segments);
    }

    private function serializeDelimitedValue(mixed $value, string $delimiter, bool $explode): string
    {
        if (!is_array($value)) {
            return $this->scalarToString($value);
        }

        if ($this->isList($value)) {
            return implode($delimiter, array_map(
                fn (mixed $item): string => $this->scalarToString($item),
                array_values($value),
            ));
        }

        $parts = [];
        foreach ($value as $property => $propertyValue) {
            if ($explode) {
                $parts[] = (string) $property . '=' . $this->scalarToString($propertyValue);
            } else {
                $parts[] = (string) $property;
                $parts[] = $this->scalarToString($propertyValue);
            }
        }

        return implode($delimiter, $parts);
    }

    private function scalarToString(mixed $value): string
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
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
