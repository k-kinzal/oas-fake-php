<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;

/**
 * Resolves and starts the runtime assembled from one server configuration.
 *
 * @visibility namespace
 */
final class ServerRuntime
{
    private ?Schema $resolvedSchema = null;

    /**
     * @param array{
     *     schema: string,
     *     mode: string,
     *     cassettePath: string,
     *     cassetteName: string,
     *     validateRequests: bool,
     *     validateResponses: bool,
     *     fakerOptions: array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int},
     *     middleware: list<MiddlewareInterface>
     * } $defaults
     */
    public function __construct(
        private ServerConfiguration $configuration,
        private HandlerMap $handlers,
        private array $defaults,
    ) {
    }

    /**
     * Forget a schema resolved before a configuration change.
     */
    public function forgetSchema(): void
    {
        $this->resolvedSchema = null;
    }

    /**
     * Assemble and start an interceptor for a server.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws ReflectionException when a declarative handler cannot be bound
     */
    public function build(Server $server): Interceptor
    {
        $schema = $this->schema();
        $handlers = clone $this->handlers;
        (new DeclarativeHandlerRegistrar())->register($server, $handlers, $schema);
        $interceptor = (new InterceptorFactory())->create(
            new ServerOptions(
                schema: $schema,
                mode: $this->mode(),
                cassettePath: $this->configuration->cassettePath($this->defaults['cassettePath']),
                validateRequests: $this->configuration->requestValidation($this->defaults['validateRequests']),
                validateResponses: $this->configuration->responseValidation($this->defaults['validateResponses']),
                fakerOptions: $this->fakerOptions(),
                middleware: $this->configuration->middleware($this->defaults['middleware']),
                cassetteName: $this->configuration->cassetteName($this->defaults['cassetteName'], $server::class),
            ),
            $handlers,
        );
        $interceptor->start();

        return $interceptor;
    }

    /**
     * Return the cached or newly resolved schema.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     */
    public function schema(): Schema
    {
        return $this->resolvedSchema ??= $this->configuration->schema($this->defaults['schema']);
    }

    /**
     * Return the resolved faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->configuration->fakerOptions($this->defaults['fakerOptions']);
    }

    /**
     * Return server URLs from the resolved schema.
     *
     * @throws IOException when the schema file cannot be read
     * @throws TypeErrorException when the schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     *
     * @return list<string>
     */
    public function serverUrls(): array
    {
        return $this->schema()->serverUrls();
    }

    /**
     * Return the active server mode.
     */
    public function mode(): Mode
    {
        return $this->configuration->mode($this->defaults['mode']);
    }
}
