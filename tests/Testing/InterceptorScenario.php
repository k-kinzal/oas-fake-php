<?php

declare(strict_types=1);

namespace OasFake\Testing;

use OasFake\HandlerMap;
use OasFake\Interceptor;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\Validator;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Owns the collaborators and cassette directory for one interceptor scenario.
 */
final class InterceptorScenario
{
    private Schema $schema;
    private Validator $validator;
    private HandlerMap $handlers;
    private string $cassettePath;

    /**
     * Build fresh collaborators and an isolated cassette directory.
     */
    public function __construct()
    {
        $this->schema = Petstore::schema();
        $this->validator = new Validator($this->schema);
        $this->handlers = new HandlerMap();
        $this->cassettePath = sys_get_temp_dir() . '/oas-fake-test-cassettes-' . spl_object_id($this);
        if (!is_dir($this->cassettePath)) {
            mkdir($this->cassettePath, 0777, true);
        }
    }

    /**
     * Remove cassette files created by the scenario.
     */
    public function __destruct()
    {
        $files = glob($this->cassettePath . '/*');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->cassettePath);
    }

    /**
     * Return the scenario schema.
     */
    public function schema(): Schema
    {
        return $this->schema;
    }

    /**
     * Return the scenario validator.
     */
    public function validator(): Validator
    {
        return $this->validator;
    }

    /**
     * Return the mutable handler map used by created interceptors.
     */
    public function handlers(): HandlerMap
    {
        return $this->handlers;
    }

    /**
     * Return the isolated cassette directory.
     */
    public function cassettePath(): string
    {
        return $this->cassettePath;
    }

    /**
     * Build an interceptor from this scenario's collaborators.
     *
     * @param list<MiddlewareInterface> $middleware
     */
    public function interceptor(
        string $mode = Mode::FAKE,
        ?string $cassettePath = null,
        bool $validateRequests = true,
        bool $validateResponses = true,
        array $middleware = [],
        string $cassetteName = 'recording',
    ): Interceptor {
        return new Interceptor(
            mode: $mode,
            cassettePath: $cassettePath ?? $this->cassettePath,
            schema: $this->schema,
            validator: $this->validator,
            fakerOptions: [],
            handlers: $this->handlers,
            validateRequests: $validateRequests,
            validateResponses: $validateResponses,
            middleware: $middleware,
            cassetteName: $cassetteName,
        );
    }
}
