<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema as CebeSchema;
use JsonException;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

/**
 * Generates fake values for OpenAPI path, query, and header parameters.
 */
final class ParameterFaker
{
    private Options $options;

    private ParameterSerializer $serializer;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     */
    public function __construct(array $fakerOptions = [])
    {
        $this->options = new Options();
        $this->serializer = new ParameterSerializer();

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
     * @throws JsonException when a generated structured parameter cannot be encoded
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
                foreach ($this->serializer->query($parameter, $value) as $name => $serializedValue) {
                    $result['query'][$name] = $serializedValue;
                }
            } else {
                $result[$in][$parameter->name] = $this->serializer->delimited($parameter, $value);
            }
        }

        return $result;
    }
}
