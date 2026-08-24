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
        $byOperationId = [];
        $byPathMethod = [];
        $openApi = $schema->openApi();
        $operationResolver = new PathOperationResolver();
        $parameterResolver = new OperationParameterResolver();

        if ($openApi->paths === null) {
            return [
                'byOperationId' => $byOperationId,
                'byPathMethod' => $byPathMethod,
            ];
        }

        /** @var PathItem $pathItem */
        foreach ($openApi->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            $pathParameters = $parameterResolver->forPath($pathItem);
            foreach (self::METHODS as $method) {
                $operation = $operationResolver->resolve($pathItem, $method);
                if (!$operation instanceof Operation) {
                    continue;
                }

                $operationId = $operation->operationId ?? '';
                $definition = new OperationInfo(
                    pathPattern: $pathPattern,
                    method: $method,
                    operationId: $operationId,
                    operation: $operation,
                    parameters: $parameterResolver->merge($pathParameters, $operation),
                    serverUrls: $schema->effectiveServerUrls($pathItem, $operation),
                );

                if ($operationId !== '') {
                    $byOperationId[$operationId] = $definition;
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
