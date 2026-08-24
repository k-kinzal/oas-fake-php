<?php

declare(strict_types=1);

namespace OasFake;

use ReflectionClass;
use ReflectionMethod;

/**
 * Registers public server methods as declarative handlers.
 *
 * @visibility namespace
 */
final class DeclarativeHandlerRegistrar
{
    /**
     * Register operationId and Route attribute handlers from the given server.
     */
    public function register(Server $server, HandlerMap $handlers, ?Schema $schema = null): void
    {
        $reflection = new ReflectionClass($server);
        $operationLookup = $schema === null ? null : new OperationLookup($schema);
        $inspector = new DeclarativeHandlerInspector();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$inspector->isCandidate($method)) {
                continue;
            }

            $closure = $method->getClosure($server);
            if ($closure === null) {
                continue;
            }

            $routeAttr = $inspector->route($method);
            if ($routeAttr !== null) {
                if ($operationLookup !== null && $operationLookup->findByPathAndMethod($routeAttr->path, $routeAttr->method) === null) {
                    continue;
                }

                $handlers->forPath(
                    $routeAttr->path,
                    $routeAttr->method,
                    Handler::callback($closure),
                );

                continue;
            }

            if ($operationLookup !== null && $operationLookup->findByOperationId($method->getName()) === null) {
                continue;
            }

            $handlers->forOperation(
                $method->getName(),
                Handler::callback($closure),
            );
        }
    }
}
