<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use OasFakePHP\Exception\SchemaNotFoundException;

trait SchemaSetting
{
    protected static ?string $SCHEMA_FILE = null;

    protected static ?string $SCHEMA_STRING = null;

    /** @var 'json'|'yaml'|null */
    protected static ?string $SCHEMA_FORMAT = null;

    private ?string $schemaFile = null;

    private ?string $schemaString = null;

    /** @var 'json'|'yaml'|null */
    private ?string $schemaFormat = null;

    private ?OpenApi $schemaInstance = null;

    public function withSchemaFile(string $path): static
    {
        $this->schemaFile = $path;

        return $this;
    }

    /**
     * @param 'json'|'yaml' $format
     */
    public function withSchemaString(string $content, string $format = 'yaml'): static
    {
        $this->schemaString = $content;
        $this->schemaFormat = $format;

        return $this;
    }

    public function withSchema(OpenApi $schema): static
    {
        $this->schemaInstance = $schema;

        return $this;
    }

    protected function schemaFile(): ?string
    {
        if ($this->schemaFile !== null) {
            return $this->schemaFile;
        }

        return static::$SCHEMA_FILE;
    }

    protected function schemaString(): ?string
    {
        if ($this->schemaString !== null) {
            return $this->schemaString;
        }

        return static::$SCHEMA_STRING;
    }

    /**
     * @return 'json'|'yaml'|null
     */
    protected function schemaFormat(): ?string
    {
        if ($this->schemaFormat !== null) {
            return $this->schemaFormat;
        }

        return static::$SCHEMA_FORMAT;
    }

    protected function resolveSchema(): OpenApi
    {
        if ($this->schemaInstance !== null) {
            return $this->schemaInstance;
        }

        $schemaFile = $this->schemaFile();
        if ($schemaFile !== null) {
            if (!file_exists($schemaFile)) {
                throw SchemaNotFoundException::forPath($schemaFile);
            }

            $extension = strtolower(pathinfo($schemaFile, PATHINFO_EXTENSION));
            if ($extension === 'json') {
                return Reader::readFromJsonFile($schemaFile, OpenApi::class, true);
            }

            return Reader::readFromYamlFile($schemaFile, OpenApi::class, true);
        }

        $schemaString = $this->schemaString();
        if ($schemaString !== null) {
            $format = $this->schemaFormat() ?? 'yaml';
            if ($format === 'json') {
                return Reader::readFromJson($schemaString, OpenApi::class);
            }

            return Reader::readFromYaml($schemaString, OpenApi::class);
        }

        throw new SchemaNotFoundException('No OpenAPI schema has been configured. Set $SCHEMA_FILE, $SCHEMA_STRING, or use withSchemaFile()/withSchemaString().');
    }
}
