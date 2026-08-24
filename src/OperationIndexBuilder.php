<?php

declare(strict_types=1);

namespace OasFake;

use function array_values;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;

use function is_string;

/**
 * Builds immutable lookup tables from the operations declared in a schema.
 */
final class OperationIndexBuilder
{
    /**
     * @return array{
     *     byOperationId: array<string, OperationDefinition>,
     *     byPathMethod: array<string, OperationDefinition>
     * }
     */
    public function build(Schema $schema): array
    {
        $byOperationId = [];
        $byPathMethod = [];
        $openApi = $schema->openApi();

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

            $pathParameters = [];
            if ($pathItem->parameters !== null) {
                foreach ($pathItem->parameters as $parameter) {
                    if ($parameter instanceof Parameter) {
                        $pathParameters[] = $parameter;
                    }
                }
            }

            foreach (['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'] as $method) {
                $operation = match ($method) {
                    'get' => $pathItem->get,
                    'post' => $pathItem->post,
                    'put' => $pathItem->put,
                    'delete' => $pathItem->delete,
                    'patch' => $pathItem->patch,
                    'options' => $pathItem->options,
                    'head' => $pathItem->head,
                    'trace' => $pathItem->trace,
                };
                if (!$operation instanceof Operation) {
                    continue;
                }

                /** @var array<string, Parameter> $mergedParameters */
                $mergedParameters = [];
                foreach ($pathParameters as $parameter) {
                    $mergedParameters[$parameter->in . ':' . $parameter->name] = $parameter;
                }
                if ($operation->parameters !== null) {
                    foreach ($operation->parameters as $parameter) {
                        if ($parameter instanceof Parameter) {
                            $mergedParameters[$parameter->in . ':' . $parameter->name] = $parameter;
                        }
                    }
                }

                $operationId = $operation->operationId ?? '';
                $definition = new OperationDefinition(
                    pathPattern: $pathPattern,
                    method: $method,
                    operationId: $operationId,
                    operation: $operation,
                    parameters: array_values($mergedParameters),
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
