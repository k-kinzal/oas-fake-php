<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;

use function is_string;
use function strtolower;

/**
 * Indexes OpenAPI operations for lookup by operationId or path/method pair.
 */
final class OperationLookup
{
    /**
     * @var array<string, OperationInfo> keyed by operationId
     */
    private array $byOperationId = [];

    /**
     * @var array<string, OperationInfo> keyed by "METHOD:/path"
     */
    private array $byPathMethod = [];

    public function __construct(Schema $schema)
    {
        $this->index($schema);
    }

    /**
     * Find an operation by its operationId.
     *
     * @param string $operationId The OpenAPI operationId
     */
    public function findByOperationId(string $operationId): ?OperationInfo
    {
        return $this->byOperationId[$operationId] ?? null;
    }

    /**
     * Find an operation by path pattern and HTTP method.
     *
     * @param string $path The OpenAPI path pattern
     * @param string $method The HTTP method (case-insensitive)
     */
    public function findByPathAndMethod(string $path, string $method): ?OperationInfo
    {
        $key = strtolower($method) . ':' . $path;

        return $this->byPathMethod[$key] ?? null;
    }

    private function index(Schema $schema): void
    {
        $openApi = $schema->openApi();

        if ($openApi->paths === null) {
            return;
        }

        /** @var PathItem $pathItem */
        foreach ($openApi->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            $pathLevelParams = $this->extractParameters($pathItem);

            foreach ($this->httpMethods() as $method) {
                $operation = $this->getOperation($pathItem, $method);
                if ($operation === null) {
                    continue;
                }

                $mergedParams = $this->mergeParameters($pathLevelParams, $this->extractOperationParameters($operation));
                $operationId = $operation->operationId ?? '';

                $info = new OperationInfo(
                    pathPattern: $pathPattern,
                    method: $method,
                    operationId: $operationId,
                    operation: $operation,
                    parameters: $mergedParams,
                );

                if ($operationId !== '') {
                    $this->byOperationId[$operationId] = $info;
                }

                $this->byPathMethod[$method . ':' . $pathPattern] = $info;
            }
        }
    }

    /**
     * @return list<Parameter>
     */
    private function extractParameters(PathItem $pathItem): array
    {
        if ($pathItem->parameters === null) {
            return [];
        }

        $params = [];
        foreach ($pathItem->parameters as $param) {
            if ($param instanceof Parameter) {
                $params[] = $param;
            }
        }

        return $params;
    }

    /**
     * @return list<Parameter>
     */
    private function extractOperationParameters(Operation $operation): array
    {
        if ($operation->parameters === null) {
            return [];
        }

        $params = [];
        foreach ($operation->parameters as $param) {
            if ($param instanceof Parameter) {
                $params[] = $param;
            }
        }

        return $params;
    }

    /**
     * Merge path-level and operation-level parameters.
     * Operation-level parameters take precedence (matched by name+in).
     *
     * @param list<Parameter> $pathParams
     * @param list<Parameter> $operationParams
     *
     * @return list<Parameter>
     */
    private function mergeParameters(array $pathParams, array $operationParams): array
    {
        /** @var array<string, Parameter> $merged */
        $merged = [];

        foreach ($pathParams as $param) {
            $key = $param->in . ':' . $param->name;
            $merged[$key] = $param;
        }

        foreach ($operationParams as $param) {
            $key = $param->in . ':' . $param->name;
            $merged[$key] = $param;
        }

        return array_values($merged);
    }

    private function getOperation(PathItem $pathItem, string $method): ?Operation
    {
        return match ($method) {
            'get' => $pathItem->get,
            'post' => $pathItem->post,
            'put' => $pathItem->put,
            'delete' => $pathItem->delete,
            'patch' => $pathItem->patch,
            'options' => $pathItem->options,
            'head' => $pathItem->head,
            'trace' => $pathItem->trace,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function httpMethods(): array
    {
        return ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];
    }
}
