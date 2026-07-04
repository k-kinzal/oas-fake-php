<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Server as CebeServer;

use function is_string;

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

        $resolvedPath = realpath($path);
        if ($resolvedPath === false) {
            throw SchemaNotFoundException::forPath($path);
        }

        $ext = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $openApi = $ext === 'json'
            ? Reader::readFromJsonFile($resolvedPath, OpenApi::class, true)
            : Reader::readFromYamlFile($resolvedPath, OpenApi::class, true);

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
     * Return the effective server URLs used by operations, with variables substituted.
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        $urls = [];

        if ($this->openApi->paths !== null) {
            /** @var PathItem $pathItem */
            foreach ($this->openApi->paths as $path => $pathItem) {
                if (!is_string($path)) {
                    continue;
                }

                foreach ($pathItem->getOperations() as $operation) {
                    foreach ($this->effectiveServerUrls($pathItem, $operation) as $url) {
                        $urls[$url] = true;
                    }
                }
            }
        }

        if ($urls === []) {
            return $this->effectiveServerUrls();
        }

        return array_keys($urls);
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
        if ($operation !== null && $operation->servers !== null && $operation->servers !== []) {
            return $this->resolveServerUrls($operation->servers);
        }

        if ($pathItem !== null && $pathItem->servers !== null && $pathItem->servers !== []) {
            return $this->resolveServerUrls($pathItem->servers);
        }

        if ($this->openApi->servers !== null && $this->openApi->servers !== []) {
            return $this->resolveServerUrls($this->openApi->servers);
        }

        return ['/'];
    }

    /**
     * @param array<int|string, CebeServer> $servers
     *
     * @return list<string>
     */
    private function resolveServerUrls(array $servers): array
    {
        $urls = [];

        foreach ($servers as $server) {
            if (!$server instanceof CebeServer) {
                continue;
            }

            $url = $server->url;
            if ($server->variables !== null) {
                foreach ($server->variables as $name => $variable) {
                    $url = str_replace('{' . $name . '}', $variable->default, $url);
                }
            }
            $urls[] = $url;
        }

        return $urls === [] ? ['/'] : $urls;
    }
}
