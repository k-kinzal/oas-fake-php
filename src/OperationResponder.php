<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function is_int;
use function is_string;
use function json_encode;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves operation handlers or schema-generated fake responses.
 */
final class OperationResponder
{
    /**
     * Create a responder backed by the shared fake-data context.
     */
    public function __construct(
        private FakeDataContext $fakeDataContext,
        private HandlerMap $handlers,
    ) {
    }

    /**
     * Return the response for a matched operation and request.
     */
    public function respond(
        ServerRequestInterface $request,
        string $path,
        string $method,
        ?OperationInfo $operationInfo,
    ): ResponseInterface {
        $operationId = $operationInfo?->operationId;
        $handler = $this->handlers->find($operationId ?? '', $path, $method, $operationInfo?->pathPattern);
        $statusCode = $this->resolveStatusCode($operationInfo);

        if ($handler !== null) {
            $fakerDefault = null;
            if ($operationInfo !== null && $this->hasResponseBody($operationInfo)) {
                $fakerDefault = FakeResponse::generateResponse($this->fakeDataContext, $operationInfo->pathPattern, $method, $statusCode);
            }

            return $handler->resolve($request, $fakerDefault);
        }

        if ($operationInfo !== null) {
            if ($this->hasResponseBody($operationInfo)) {
                return FakeResponse::generateResponse($this->fakeDataContext, $operationInfo->pathPattern, $method, $statusCode);
            }

            return new Response($statusCode);
        }

        return new Response(500, ['Content-Type' => 'application/json'], (string) json_encode([
            'error' => 'Could not resolve operation from request',
        ]));
    }

    private function hasResponseBody(OperationInfo $operationInfo): bool
    {
        if ($operationInfo->operation->responses !== null) {
            foreach ($operationInfo->operation->responses as $code => $response) {
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

    private function resolveStatusCode(?OperationInfo $operationInfo): int
    {
        if ($operationInfo?->operation->responses !== null) {
            foreach ($operationInfo->operation->responses as $code => $response) {
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
}
