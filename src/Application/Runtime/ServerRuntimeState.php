<?php

declare(strict_types=1);

namespace OasFake;

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
}
