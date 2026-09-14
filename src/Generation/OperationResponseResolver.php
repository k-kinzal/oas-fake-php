<?php

declare(strict_types=1);

namespace OasFake;

use function array_keys;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;
use cebe\openapi\spec\Schema as CebeSchema;

use function is_int;

/**
 * Resolves successful response metadata for an indexed OpenAPI operation.
 *
 * @visibility namespace
 */
final class OperationResponseResolver
{
    /**
     * Return the first declared successful status, falling back to 200.
     */
    public function defaultStatusCode(OperationInfo $definition): int
    {
        return $this->successfulResponse($definition)['status'] ?? 200;
    }

    /**
     * Check whether the first declared successful response has content.
     */
    public function hasSuccessfulBody(OperationInfo $definition): bool
    {
        $successful = $this->successfulResponse($definition);

        return $successful === null
            || $successful['response']->content !== [];
    }

    /**
     * Return the response object declared for an exact status code.
     */
    public function forStatus(OperationInfo $definition, int $statusCode): ?Response
    {
        $responses = $definition->operation->responses;
        if (!$responses instanceof Responses) {
            return null;
        }

        $response = $responses->getResponse((string) $statusCode);

        return $response instanceof Response ? $response : null;
    }

    /**
     * Return the preferred media type for an exact response status.
     */
    public function mediaType(OperationInfo $definition, int $statusCode): string
    {
        $response = $this->forStatus($definition, $statusCode);
        if ($response === null || $response->content === []) {
            return 'application/json';
        }

        /** @var array<string, MediaType> $content */
        $content = $response->content;

        return PayloadSerializer::preferredMediaType(array_keys($content));
    }

    /**
     * Return the schema declared for a response status and media type.
     */
    public function schema(OperationInfo $definition, int $statusCode, string $mediaType): ?CebeSchema
    {
        $response = $this->forStatus($definition, $statusCode);
        if ($response === null) {
            return null;
        }

        /** @var array<string, MediaType> $content */
        $content = $response->content;
        $selectedMediaType = $content[$mediaType] ?? null;

        return $selectedMediaType?->schema instanceof CebeSchema ? $selectedMediaType->schema : null;
    }

    /**
     * Return the first concrete 2xx response and its status.
     *
     * @return array{status: int, response: Response}|null
     */
    public function successfulResponse(OperationInfo $definition): ?array
    {
        $responses = $definition->operation->responses;
        if (!$responses instanceof Responses) {
            return null;
        }

        foreach ($responses->getResponses() as $code => $response) {
            if (is_int($code) && $code >= 200 && $code < 300 && $response instanceof Response) {
                return ['status' => $code, 'response' => $response];
            }
        }

        return null;
    }
}
