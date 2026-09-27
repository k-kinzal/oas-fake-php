<?php

declare(strict_types=1);

namespace OasFake;

use function count;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

/**
 * Recognizes public server methods that satisfy the handler contract.
 *
 * @visibility namespace
 */
final class DeclarativeHandlerInspector
{
    /**
     * Check whether a reflected method can handle an intercepted request.
     */
    public function isCandidate(ReflectionMethod $method): bool
    {
        if ($method->isConstructor() || $method->isDestructor() || $method->isStatic()) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() === Server::class) {
            return false;
        }

        $types = new HandlerTypeMatcher();
        $parameters = $method->getParameters();
        if (count($parameters) === 0 || count($parameters) > 2) {
            return false;
        }

        if (!$types->allows($parameters[0]->getType(), ServerRequestInterface::class)) {
            return false;
        }

        if (isset($parameters[1])) {
            $responseType = $parameters[1]->getType();
            if (!$types->allowsNullable($responseType, ResponseInterface::class)) {
                return false;
            }
        }

        $returnType = $method->getReturnType();

        return $returnType === null || $types->allows($returnType, ResponseInterface::class);
    }

    /**
     * Return the first route attribute declared on a handler method.
     */
    public function route(ReflectionMethod $method): ?Route
    {
        $attributes = $method->getAttributes(Route::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }
}
