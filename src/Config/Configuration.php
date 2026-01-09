<?php

declare(strict_types=1);

namespace OasFakePHP\Config;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OasFakePHP\Exception\SchemaNotFoundException;
use OasFakePHP\Vcr\Mode;

final class Configuration
{
    private ?OpenApi $schema = null;
    private Mode $mode;
    private string $cassettePath = './cassettes';
    private bool $validateRequests = true;
    private bool $validateResponses = true;

    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    private array $fakerOptions = [];

    public function __construct()
    {
        $this->mode = Mode::fromEnvironment();
    }

    public function fromYamlFile(string $path): self
    {
        if (!file_exists($path)) {
            throw SchemaNotFoundException::forPath($path);
        }

        $this->schema = Reader::readFromYamlFile($path, OpenApi::class, true);

        return $this;
    }

    public function fromJsonFile(string $path): self
    {
        if (!file_exists($path)) {
            throw SchemaNotFoundException::forPath($path);
        }

        $this->schema = Reader::readFromJsonFile($path, OpenApi::class, true);

        return $this;
    }

    public function fromJsonString(string $json): self
    {
        $this->schema = Reader::readFromJson($json, OpenApi::class);

        return $this;
    }

    public function fromYamlString(string $yaml): self
    {
        $this->schema = Reader::readFromYaml($yaml, OpenApi::class);

        return $this;
    }

    public function fromSchema(OpenApi $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function setMode(Mode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function setCassettePath(string $path): self
    {
        $this->cassettePath = $path;

        return $this;
    }

    public function enableRequestValidation(bool $enable = true): self
    {
        $this->validateRequests = $enable;

        return $this;
    }

    public function enableResponseValidation(bool $enable = true): self
    {
        $this->validateResponses = $enable;

        return $this;
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function setFakerOptions(array $options): self
    {
        $this->fakerOptions = $options;

        return $this;
    }

    public function getSchema(): OpenApi
    {
        if ($this->schema === null) {
            throw new SchemaNotFoundException('No OpenAPI schema has been loaded. Call fromYamlFile(), fromJsonFile(), fromJsonString(), fromYamlString(), or fromSchema() first.');
        }

        return $this->schema;
    }

    public function hasSchema(): bool
    {
        return $this->schema !== null;
    }

    public function getMode(): Mode
    {
        return $this->mode;
    }

    public function getCassettePath(): string
    {
        return $this->cassettePath;
    }

    public function shouldValidateRequests(): bool
    {
        return $this->validateRequests;
    }

    public function shouldValidateResponses(): bool
    {
        return $this->validateResponses;
    }

    /**
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function getFakerOptions(): array
    {
        return $this->fakerOptions;
    }
}
