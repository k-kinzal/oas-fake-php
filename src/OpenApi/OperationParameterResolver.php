<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;

/**
 * Resolves effective OpenAPI parameters across path and operation scopes.
 *
 * @visibility namespace
 */
final class OperationParameterResolver
{
    /**
     * Return typed parameters declared at path scope.
     *
     * @return list<Parameter>
     */
    public function forPath(PathItem $pathItem): array
    {
        $parameters = [];
        foreach ($pathItem->parameters ?? [] as $parameter) {
            if ($parameter instanceof Parameter) {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * Merge path and operation parameters according to OpenAPI override semantics.
     *
     * @param list<Parameter> $pathParameters
     *
     * @return list<Parameter>
     */
    public function merge(array $pathParameters, Operation $operation): array
    {
        /** @var array<string, Parameter> $merged */
        $merged = [];
        foreach ($pathParameters as $parameter) {
            $merged[$parameter->in . ':' . $parameter->name] = $parameter;
        }
        foreach ($operation->parameters ?? [] as $parameter) {
            if ($parameter instanceof Parameter) {
                $merged[$parameter->in . ':' . $parameter->name] = $parameter;
            }
        }

        return array_values($merged);
    }
}
