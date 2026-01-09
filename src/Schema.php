<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OasFake\Exception\SchemaNotFoundException;

/**
 * Wrapper around an OpenAPI specification with factory methods for loading.
 */
final class Schema
{
    private function __construct(private OpenApi $openApi)
    {
    }

    /**
     * Load an OpenAPI schema from a file path.
     *
     * Automatically detects JSON or YAML format from the file extension.
     *
     * @param string $path Path to the OpenAPI schema file
     *
     * @throws SchemaNotFoundException If the file does not exist
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw SchemaNotFoundException::forPath($path);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $openApi = $ext === 'json'
            ? Reader::readFromJsonFile($path, OpenApi::class, true)
            : Reader::readFromYamlFile($path, OpenApi::class, true);

        return new self($openApi);
    }

    /**
     * Parse an OpenAPI schema from a string.
     *
     * @param string $content The raw schema content
     * @param string $format The format of the content ('yaml' or 'json')
     */
    public static function fromString(string $content, string $format = 'yaml'): self
    {
        $openApi = $format === 'json'
            ? Reader::readFromJson($content, OpenApi::class)
            : Reader::readFromYaml($content, OpenApi::class);

        return new self($openApi);
    }

    /**
     * Create a Schema instance from an existing OpenApi object.
     *
     * @param OpenApi $openApi The parsed OpenAPI specification
     */
    public static function fromOpenApi(OpenApi $openApi): self
    {
        return new self($openApi);
    }

    /**
     * Return the underlying OpenApi specification object.
     */
    public function openApi(): OpenApi
    {
        return $this->openApi;
    }

    /**
     * Return the server URLs defined in the schema, with variables substituted.
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        $urls = [];
        if ($this->openApi->servers !== null) {
            foreach ($this->openApi->servers as $server) {
                $url = $server->url;
                if ($server->variables !== null) {
                    foreach ($server->variables as $name => $variable) {
                        $url = str_replace('{' . $name . '}', $variable->default, $url);
                    }
                }
                $urls[] = $url;
            }
        }

        return $urls === [] ? ['/'] : $urls;
    }
}
