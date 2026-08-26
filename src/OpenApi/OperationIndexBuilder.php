<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

use function is_string;

/**
 * Builds immutable lookup tables from the operations declared in a schema.
 *
 * @visibility namespace
 */
final class OperationIndexBuilder
{
    /** @var list<string> */
    private const METHODS = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];

    /**
     * @return array{
     *     byOperationId: array<string, OperationInfo>,
     *     byPathMethod: array<string, OperationInfo>
     * }
     */
    public function build(Schema $schema): array
    {
        $byOperationId = $byPathMethod = [];
        $operationResolver = new PathOperationResolver();
        $parameterResolver = new OperationParameterResolver();

        /** @var PathItem $pathItem */
        foreach ($schema->openApi()->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            $pathParameters = $parameterResolver->forPath($pathItem);
            foreach (self::METHODS as $method) {
                $operation = $operationResolver->resolve($pathItem, $method);
                if (!$operation instanceof Operation) {
                    continue;
                }

                $definition = (new OperationInfoFactory())->create(
                    $schema,
                    $pathItem,
                    $pathPattern,
                    $method,
                    $operation,
                    $pathParameters,
                );

                if ($definition->operationId !== '') {
                    $byOperationId[$definition->operationId] = $definition;
                }
                $byPathMethod[$method . ':' . $pathPattern] = $definition;
            }
        }

        return [
            'byOperationId' => $byOperationId,
            'byPathMethod' => $byPathMethod,
        ];
    }
}
