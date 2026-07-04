<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Builds interceptors from resolved server options.
 */
final class InterceptorFactory
{
    /**
     * Create an interceptor for the given server options and handlers.
     */
    public function create(ServerOptions $options, HandlerMap $handlers): Interceptor
    {
        return new Interceptor(
            mode: $options->mode,
            cassettePath: $options->cassettePath,
            schema: $options->schema,
            validator: new Validator($options->schema),
            fakerOptions: $options->fakerOptions,
            handlers: $handlers,
            validateRequests: $options->validateRequests,
            validateResponses: $options->validateResponses,
            middleware: $options->middleware,
            cassetteName: $options->cassetteName,
        );
    }
}
