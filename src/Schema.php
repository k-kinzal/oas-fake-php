<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use OasFake\Exception\SchemaNotFoundException;
use OasFake\Exception\SchemaParseException;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Wrapper around an OpenAPI specification with factory methods for loading.
 *
 * @visibility public
 *
 * @example Parsing an inline OpenAPI document
 *     $schema = \OasFake\Schema::fromString('{"openapi":"3.0.0","info":{"title":"Example","version":"1.0.0"},"paths":{}}', 'json');
 *     $schema->openApi()->openapi // => '3.0.0'
 */
final class Schema
{
    /**
     * Create a schema wrapper for a parsed OpenAPI document.
     */
    public function __construct(private OpenApi $openApi)
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
     * @throws SchemaParseException If the schema cannot be parsed
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw SchemaNotFoundException::forPath($path);
        }

        $resolvedPath = realpath($path);
        if ($resolvedPath === false) {
            throw SchemaNotFoundException::forPath($path);
        }

        $ext = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        try {
            if ($ext !== 'json') {
                Yaml::parseFile($resolvedPath);
            }

            $openApi = $ext === 'json'
                ? Reader::readFromJsonFile($resolvedPath, OpenApi::class, true)
                : Reader::readFromYamlFile($resolvedPath, OpenApi::class, true);
        } catch (IOException|TypeErrorException|UnresolvableReferenceException|InvalidJsonPointerSyntaxException|YamlParseException $exception) {
            throw SchemaParseException::forSource($resolvedPath, $exception);
        }

        return new self($openApi);
    }

    /**
     * Parse an OpenAPI schema from a string.
     *
     * @param string $content The raw schema content
     * @param string $format The format of the content ('yaml' or 'json')
     *
     * @throws SchemaParseException If the schema cannot be parsed
     */
    public static function fromString(string $content, string $format = 'yaml'): self
    {
        try {
            if ($format !== 'json') {
                Yaml::parse($content);
            }

            $openApi = $format === 'json'
                ? Reader::readFromJson($content, OpenApi::class)
                : Reader::readFromYaml($content, OpenApi::class);
        } catch (TypeErrorException|YamlParseException $exception) {
            throw SchemaParseException::forSource('inline ' . $format, $exception);
        }

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
     * Return the effective server URLs used by operations, with variables substituted.
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        return (new OpenApiServerResolver())->all($this->openApi);
    }

    /**
     * Return the server URLs that apply to one operation.
     *
     * Operation-level servers override path-level servers, which override root-level servers.
     *
     * @return list<string>
     */
    public function effectiveServerUrls(?PathItem $pathItem = null, ?Operation $operation = null): array
    {
        return (new OpenApiServerResolver())->effective($this->openApi, $pathItem, $operation);
    }
}
