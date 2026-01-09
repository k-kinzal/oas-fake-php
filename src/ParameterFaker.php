<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema as CebeSchema;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

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
     * @return array{path: array<string, string>, query: array<string, string>, header: array<string, string>}
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

            if (is_array($value)) {
                $stringValue = json_encode($value, JSON_THROW_ON_ERROR);
            } else {
                $stringValue = (string) $value;
            }

            $result[$in][$parameter->name] = $stringValue;
        }

        return $result;
    }
}
