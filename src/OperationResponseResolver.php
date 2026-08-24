<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema as CebeSchema;

use function is_int;
use function is_string;

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
    public function defaultStatusCode(OperationDefinition $definition): int
    {
        if ($definition->operation->responses !== null) {
            foreach ($definition->operation->responses as $code => $_response) {
                if (!is_int($code) && !is_string($code)) {
                    continue;
                }

                $numericCode = (int) $code;
                if ($numericCode >= 200 && $numericCode < 300) {
                    return $numericCode;
                }
            }
        }

        return 200;
    }

    /**
     * Check whether the first declared successful response has content.
     */
    public function hasSuccessfulBody(OperationDefinition $definition): bool
    {
        if ($definition->operation->responses !== null) {
            foreach ($definition->operation->responses as $code => $response) {
                if (!is_int($code) && !is_string($code)) {
                    continue;
                }

                $numericCode = (int) $code;
                if ($numericCode >= 200 && $numericCode < 300) {
                    return $response->content !== null && $response->content !== [];
                }
            }
        }

        return true;
    }

    /**
     * Return the response object declared for an exact status code.
     */
    public function forStatus(OperationDefinition $definition, int $statusCode): ?Response
    {
        if ($definition->operation->responses === null) {
            return null;
        }

        foreach ($definition->operation->responses as $code => $response) {
            if (!is_int($code) && !is_string($code)) {
                continue;
            }

            if ((string) $code === (string) $statusCode && $response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Return the preferred media type for an exact response status.
     */
    public function mediaType(OperationDefinition $definition, int $statusCode): string
    {
        $response = $this->forStatus($definition, $statusCode);
        if ($response === null || $response->content === null || $response->content === []) {
            return 'application/json';
        }

        $mediaTypes = [];
        foreach ($response->content as $mediaType => $_content) {
            if (is_int($mediaType) || is_string($mediaType)) {
                $mediaTypes[] = (string) $mediaType;
            }
        }

        return PayloadSerializer::preferredMediaType($mediaTypes);
    }

    /**
     * Return the schema declared for a response status and media type.
     */
    public function schema(OperationDefinition $definition, int $statusCode, string $mediaType): ?CebeSchema
    {
        $response = $this->forStatus($definition, $statusCode);
        if ($response === null || $response->content === null) {
            return null;
        }

        foreach ($response->content as $candidateMediaType => $content) {
            if ((!is_int($candidateMediaType) && !is_string($candidateMediaType)) || (string) $candidateMediaType !== $mediaType || !$content instanceof MediaType) {
                continue;
            }

            return $content->schema instanceof CebeSchema ? $content->schema : null;
        }

        return null;
    }
}
