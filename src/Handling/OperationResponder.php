<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function json_encode;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoResponse;

/**
 * Resolves operation handlers or schema-generated fake responses.
 *
 * @visibility namespace
 */
final class OperationResponder
{
    private OperationResponseResolver $responseResolver;

    /**
     * Create a responder backed by the shared fake-data context.
     */
    public function __construct(
        private FakeDataContext $fakeDataContext,
        private HandlerMap $handlers,
    ) {
        $this->responseResolver = new OperationResponseResolver();
    }

    /**
     * Return the response for a matched operation and request.
     *
     * @throws JsonException when a generated response body cannot be encoded
     * @throws NoPath when the OpenAPI path cannot be generated
     * @throws NoResponse when the OpenAPI response cannot be generated
     */
    public function respond(
        ServerRequestInterface $request,
        string $path,
        string $method,
        ?OperationInfo $operationInfo,
    ): ResponseInterface {
        $operationId = $operationInfo?->operationId;
        $handler = $this->handlers->find($operationId ?? '', $path, $method, $operationInfo?->pathPattern);

        if ($operationInfo === null) {
            if ($handler !== null) {
                return $handler->resolve($request, null);
            }

            return new Response(500, ['Content-Type' => 'application/json'], (string) json_encode([
                'error' => 'Could not resolve operation from request',
            ]));
        }

        $statusCode = $this->responseResolver->defaultStatusCode($operationInfo);
        $fakerDefault = $this->responseResolver->hasSuccessfulBody($operationInfo)
            ? (new FakeResponseFactory())->create($this->fakeDataContext, $operationInfo->pathPattern, $method, $statusCode)
            : null;

        if ($handler !== null) {
            return $handler->resolve($request, $fakerDefault);
        }

        return $fakerDefault ?? new Response($statusCode);
    }
}
