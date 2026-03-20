<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OasFake\Exception\SchemaNotFoundException;

final class Schema
{
    private function __construct(private readonly OpenApi $openApi)
    {
    }

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

    public static function fromString(string $content, string $format = 'yaml'): self
    {
        $openApi = $format === 'json'
            ? Reader::readFromJson($content, OpenApi::class)
            : Reader::readFromYaml($content, OpenApi::class);

        return new self($openApi);
    }

    public static function fromOpenApi(OpenApi $openApi): self
    {
        return new self($openApi);
    }

    public function openApi(): OpenApi
    {
        return $this->openApi;
    }
}
