<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function json_encode;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     */
    public function respond(
        ServerRequestInterface $request,
        string $path,
        string $method,
        ?OperationDefinition $definition,
    ): ResponseInterface {
        $operationId = $definition?->operationId;
        $handler = $this->handlers->find($operationId ?? '', $path, $method, $definition?->pathPattern);
        $statusCode = $definition === null ? 200 : $this->responseResolver->defaultStatusCode($definition);

        if ($handler !== null) {
            $fakerDefault = null;
            if ($definition !== null && $this->responseResolver->hasSuccessfulBody($definition)) {
                $fakerDefault = FakeResponse::generateResponse($this->fakeDataContext, $definition->pathPattern, $method, $statusCode);
            }

            return $handler->resolve($request, $fakerDefault);
        }

        if ($definition !== null) {
            if ($this->responseResolver->hasSuccessfulBody($definition)) {
                return FakeResponse::generateResponse($this->fakeDataContext, $definition->pathPattern, $method, $statusCode);
            }

            return new Response($statusCode);
        }

        return new Response(500, ['Content-Type' => 'application/json'], (string) json_encode([
            'error' => 'Could not resolve operation from request',
        ]));
    }
}
