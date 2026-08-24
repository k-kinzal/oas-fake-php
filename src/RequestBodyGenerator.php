<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema as CebeSchema;
use JsonException;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoRequest;

/**
 * Generates and serializes the body declared by an OpenAPI request operation.
 *
 * @visibility namespace
 */
final class RequestBodyGenerator
{
    /**
     * Generate a body together with the media type that determines its wire representation.
     *
     * @throws NoPath when the operation path cannot be generated
     * @throws NoRequest when the operation has no request definition
     * @throws JsonException when structured data cannot be serialized
     *
     * @return array{body: ?string, mediaType: string}
     */
    public function generate(FakeDataContext $context, OperationDefinition $definition): array
    {
        $requestBody = $definition->operation->requestBody;
        $mediaTypes = [];
        if ($requestBody instanceof RequestBody && $requestBody->content !== null) {
            foreach ($requestBody->content as $candidate => $_content) {
                if (is_int($candidate) || is_string($candidate)) {
                    $mediaTypes[] = (string) $candidate;
                }
            }
        }

        $mediaType = PayloadSerializer::preferredMediaType($mediaTypes);
        $schema = null;
        if ($requestBody instanceof RequestBody && $requestBody->content !== null) {
            foreach ($requestBody->content as $candidate => $content) {
                if ((is_int($candidate) || is_string($candidate)) && (string) $candidate === $mediaType && $content instanceof MediaType) {
                    $schema = $content->schema instanceof CebeSchema ? $content->schema : null;
                    break;
                }
            }
        }

        $fakeData = $schema !== null && !PayloadSerializer::isJsonMediaType($mediaType)
            ? $context->mockSchema($schema)
            : $context->mockRequest($definition->pathPattern, $definition->method);

        return [
            'body' => $fakeData === null ? null : PayloadSerializer::serialize($fakeData, $mediaType),
            'mediaType' => $mediaType,
        ];
    }
}
