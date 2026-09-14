<?php

declare(strict_types=1);

namespace OasFake;

use function array_keys;

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
    public function generate(FakeDataContext $context, OperationInfo $definition): array
    {
        $requestBody = $definition->operation->requestBody;

        /** @var array<string, MediaType> $content */
        $content = $requestBody instanceof RequestBody ? ($requestBody->content ?? []) : [];
        $mediaType = PayloadSerializer::preferredMediaType(array_keys($content));
        $selectedMediaType = $content[$mediaType] ?? null;
        $schema = $selectedMediaType?->schema instanceof CebeSchema ? $selectedMediaType->schema : null;

        $fakeData = $schema === null
            ? $context->mockRequest($definition->pathPattern, $definition->method)
            : $context->mockSchema($schema);

        return [
            'body' => $fakeData === null ? null : PayloadSerializer::serialize($fakeData, $mediaType),
            'mediaType' => $mediaType,
        ];
    }
}
