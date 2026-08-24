<?php

declare(strict_types=1);

namespace OasFake;

use JsonException;
use OasFake\Exception\FakeGenerationException;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoRequest;

/**
 * Builds a fake request from an indexed OpenAPI operation.
 *
 * @visibility namespace
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
                $payload = (new RequestBodyGenerator())->generate($context, $definition);
                $body = $payload['body'];
                if ($body !== null) {
                    $headers['Content-Type'] ??= $payload['mediaType'];
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
