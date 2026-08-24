<?php

declare(strict_types=1);

namespace OasFake;

use JsonException;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoRequest;

/**
 * Generates the constructor state for a fake request.
 *
 * @visibility namespace
 */
final class FakeRequestFactory
{
    /**
     * Generate schema-compliant request state.
     *
     * @throws JsonException when the generated request body cannot be encoded
     * @throws NoPath when the OpenAPI path cannot be generated
     * @throws NoRequest when the OpenAPI request cannot be generated
     *
     * @return array{
     *     method: string,
     *     baseUrl: string,
     *     pathPattern: string,
     *     pathParams: array<string, string>,
     *     queryParams: array<string, list<string>|string>,
     *     headerParams: array<string, string>,
     *     rawBody: string|null
     * }
     */
    public function create(FakeDataContext $context, OperationInfo $definition): array
    {
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

        return [
            'method' => $definition->method,
            'baseUrl' => $definition->serverUrls[0] ?? '/',
            'pathPattern' => $definition->pathPattern,
            'pathParams' => $parameters['path'],
            'queryParams' => $parameters['query'],
            'headerParams' => $headers,
            'rawBody' => $body,
        ];
    }
}
