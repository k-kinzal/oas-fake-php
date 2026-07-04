<?php

declare(strict_types=1);

namespace OasFake;

use function count;
use function is_a;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;

/**
 * Registers public server methods as declarative handlers.
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

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$this->isDeclarativeHandlerCandidate($method)) {
                continue;
            }

            $closure = $method->getClosure($server);
            if ($closure === null) {
                continue;
            }

            $routeAttr = $this->getRouteAttribute($method);
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

    private function isDeclarativeHandlerCandidate(ReflectionMethod $method): bool
    {
        if ($method->isConstructor() || $method->isDestructor() || $method->isStatic()) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() === Server::class) {
            return false;
        }

        return $this->hasHandlerSignature($method);
    }

    private function getRouteAttribute(ReflectionMethod $method): ?Route
    {
        $attributes = $method->getAttributes(Route::class);
        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function hasHandlerSignature(ReflectionMethod $method): bool
    {
        $parameters = $method->getParameters();
        if (count($parameters) === 0 || count($parameters) > 2) {
            return false;
        }

        if (!$this->typeAllows($parameters[0]->getType(), ServerRequestInterface::class)) {
            return false;
        }

        if (isset($parameters[1]) && !$this->isDefaultResponseParameter($parameters[1])) {
            return false;
        }

        $returnType = $method->getReturnType();

        return $returnType === null || $this->typeAllows($returnType, ResponseInterface::class);
    }

    private function isDefaultResponseParameter(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if (!$this->typeAllows($type, ResponseInterface::class)) {
            return false;
        }

        return $type instanceof ReflectionNamedType && $type->allowsNull();
    }

    private function typeAllows(?ReflectionType $type, string $expected): bool
    {
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return is_a($type->getName(), $expected, true);
    }
}
