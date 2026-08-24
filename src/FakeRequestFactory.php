<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema as CebeSchema;

use function is_int;
use function is_string;

use JsonException;
use OasFake\Exception\FakeGenerationException;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoRequest;

/**
 * Builds a fake request from an indexed OpenAPI operation.
 */
final class FakeRequestFactory
{
    /**
     * Create a schema-compliant request value.
     *
     * @throws FakeGenerationException when parameters or body data cannot be generated
     */
    public function create(FakeDataContext $context, OperationDefinition $definition): FakeRequest
    {
        try {
            $parameters = (new ParameterFaker($context->fakerOptions()))->generate($definition->parameters);
            $body = null;
            $headers = $parameters['header'];

            if ($definition->operation->requestBody !== null) {
                $requestBody = $definition->operation->requestBody;
                $mediaTypes = [];
                if ($requestBody instanceof RequestBody && $requestBody->content !== null) {
                    foreach ($requestBody->content as $mediaType => $_content) {
                        if (is_int($mediaType) || is_string($mediaType)) {
                            $mediaTypes[] = (string) $mediaType;
                        }
                    }
                }
                $mediaType = PayloadSerializer::preferredMediaType($mediaTypes);

                $schema = null;
                if ($requestBody instanceof RequestBody && $requestBody->content !== null) {
                    foreach ($requestBody->content as $candidateMediaType => $content) {
                        if ((!is_int($candidateMediaType) && !is_string($candidateMediaType)) || (string) $candidateMediaType !== $mediaType || !$content instanceof MediaType) {
                            continue;
                        }

                        $schema = $content->schema instanceof CebeSchema ? $content->schema : null;
                        break;
                    }
                }

                $fakeData = $schema instanceof CebeSchema && !PayloadSerializer::isJsonMediaType($mediaType)
                    ? $context->mockSchema($schema)
                    : $context->mockRequest($definition->pathPattern, $definition->method);

                if ($fakeData !== null) {
                    $body = PayloadSerializer::serialize($fakeData, $mediaType);
                    $headers['Content-Type'] ??= $mediaType;
                }
            }
        } catch (NoPath|NoRequest|JsonException $exception) {
            throw FakeGenerationException::forOperation($definition->operationId, $exception);
        }

        return new FakeRequest(
            method: $definition->method,
            baseUrl: $definition->serverUrls[0] ?? '/',
            pathPattern: $definition->pathPattern,
            pathParams: $parameters['path'],
            queryParams: $parameters['query'],
            headerParams: $headers,
            rawBody: $body,
        );
    }
}
