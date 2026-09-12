<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;

/**
 * Composes the mutable configuration and runtime owned by a server instance.
 *
 * @visibility namespace
 */
trait ServerRuntimeState
{
    protected static string $SCHEMA = '';
    protected static string $MODE = 'fake';
    protected static string $CASSETTE_PATH = './cassettes';
    protected static string $CASSETTE_NAME = '';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;

    /**
     * @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    protected static array $FAKER_OPTIONS = [];

    private HandlerMap $handlers;
    private ServerConfiguration $configuration;
    private ServerLifecycle $lifecycle;
    private ServerRuntime $runtime;

    /**
     * Create the configuration, handlers, lifecycle, and runtime owned by the server.
     */
    public function __construct()
    {
        $this->configuration = new ServerConfiguration();
        $this->handlers = new HandlerMap();
        $this->lifecycle = new ServerLifecycle();
        $this->runtime = new ServerRuntime($this->configuration, $this->handlers, [
            'schema' => static::$SCHEMA,
            'mode' => static::$MODE,
            'cassettePath' => static::$CASSETTE_PATH,
            'cassetteName' => static::$CASSETTE_NAME,
            'validateRequests' => static::$VALIDATE_REQUESTS,
            'validateResponses' => static::$VALIDATE_RESPONSES,
            'fakerOptions' => static::$FAKER_OPTIONS,
            'middleware' => static::middleware(),
        ]);
    }

    /**
     * Return the resolved OpenAPI schema.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     *
     * @mutation $this
     */
    public function schema(): Schema
    {
        return $this->runtime->schema();
    }

    /**
     * Return the resolved faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->runtime->fakerOptions();
    }

    /**
     * Return the server URLs from the resolved schema.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     *
     * @return list<string>
     *
     * @mutation $this
     */
    public function serverUrls(): array
    {
        return $this->runtime->serverUrls();
    }

    /**
     * Resolve the active mode from environment, fluent override, or static defaults.
     */
    public function resolveMode(): Mode
    {
        return $this->runtime->mode();
    }
}
