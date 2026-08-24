<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Schema as CebeSchema;
use GuzzleHttp\Psr7\Response;
use JsonException;
use OasFake\Exception\FakeGenerationException;
use Psr\Http\Message\ResponseInterface;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoResponse;

/**
 * Generates PSR-7 responses from schema-defined response contracts.
 *
 * @visibility namespace
 */
final class FakeResponseFactory
{
    /**
     * Generate a response for a schema path, method, and status code.
     *
     * @throws FakeGenerationException when response data cannot be generated
     */
    public function create(
        FakeDataContext $context,
        string $path,
        string $method,
        int $statusCode,
    ): ResponseInterface {
        $definition = $context->operationLookup()->findByPathAndMethod($path, $method);
        $resolver = new OperationResponseResolver();
        $mediaType = $definition === null ? 'application/json' : $resolver->mediaType($definition, $statusCode);
        $schema = $definition === null ? null : $resolver->schema($definition, $statusCode, $mediaType);

        try {
            $data = $schema instanceof CebeSchema && !PayloadSerializer::isJsonMediaType($mediaType)
                ? $context->mockSchema($schema)
                : $context->mockResponse($path, $method, $statusCode);

            return new Response(
                $statusCode,
                ['Content-Type' => $mediaType],
                PayloadSerializer::serialize($data, $mediaType),
            );
        } catch (NoPath|NoResponse|JsonException $exception) {
            throw FakeGenerationException::forOperation(strtoupper($method) . ' ' . $path, $exception);
        }
    }
}
