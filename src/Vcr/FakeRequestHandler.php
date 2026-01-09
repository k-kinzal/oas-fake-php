<?php

declare(strict_types=1);

namespace OasFakePHP\Vcr;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

use function is_string;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Http\RequestConverter;
use OasFakePHP\Http\ResponseConverter;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Response\FakeResponseGenerator;
use OasFakePHP\Validation\RequestValidator;
use OasFakePHP\Validation\ResponseValidator;
use Psr\Http\Message\ServerRequestInterface;

use function strtolower;

use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

final class FakeRequestHandler
{
    private readonly RequestConverter $requestConverter;
    private readonly ResponseConverter $responseConverter;
    private readonly RequestValidator $requestValidator;
    private readonly ResponseValidator $responseValidator;
    private readonly FakeResponseGenerator $fakeResponseGenerator;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CallbackRegistry $callbackRegistry,
    ) {
        $schema = $this->configuration->getSchema();
        $this->requestConverter = new RequestConverter();
        $this->responseConverter = new ResponseConverter();
        $this->requestValidator = new RequestValidator($schema);
        $this->responseValidator = new ResponseValidator($schema);
        $this->fakeResponseGenerator = new FakeResponseGenerator(
            $schema,
            $this->configuration->getFakerOptions(),
        );
    }

    public function handleRequest(VcrRequest $vcrRequest): VcrResponse
    {
        $psr7Request = $this->requestConverter->vcrToPsr7($vcrRequest);

        // Validate request
        $operation = $this->requestValidator->validate($psr7Request);

        // Generate fake response
        $fakeResponse = $this->fakeResponseGenerator->generate($operation);

        // Check for custom callback
        $operationId = $this->findOperationId($psr7Request);

        if ($operationId !== null && $this->callbackRegistry->hasForOperationId($operationId)) {
            $fakeResponse = $this->callbackRegistry->execute(
                $operation,
                $psr7Request,
                $fakeResponse,
                $operationId,
            );
        } elseif ($this->callbackRegistry->has($operation)) {
            $fakeResponse = $this->callbackRegistry->execute(
                $operation,
                $psr7Request,
                $fakeResponse,
            );
        }

        // Validate response
        if ($this->configuration->shouldValidateResponses()) {
            $this->responseValidator->validate($operation, $fakeResponse);
        }

        return $this->responseConverter->psr7ToVcr($fakeResponse);
    }

    public function validateRequest(VcrRequest $vcrRequest): void
    {
        if (!$this->configuration->shouldValidateRequests()) {
            return;
        }

        $psr7Request = $this->requestConverter->vcrToPsr7($vcrRequest);
        $this->requestValidator->validate($psr7Request);
    }

    public function validateResponse(VcrRequest $vcrRequest, VcrResponse $vcrResponse): void
    {
        if (!$this->configuration->shouldValidateResponses()) {
            return;
        }

        $psr7Request = $this->requestConverter->vcrToPsr7($vcrRequest);
        $psr7Response = $this->responseConverter->vcrToPsr7($vcrResponse);

        $operation = $this->requestValidator->validate($psr7Request);
        $this->responseValidator->validate($operation, $psr7Response);
    }

    private function findOperationId(ServerRequestInterface $request): ?string
    {
        $schema = $this->configuration->getSchema();
        $path = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());

        if ($schema->paths === null) {
            return null;
        }

        /** @var PathItem $pathItem */
        foreach ($schema->paths as $pathPattern => $pathItem) {
            if (!is_string($pathPattern)) {
                continue;
            }

            if ($this->matchPath($pathPattern, $path)) {
                $operation = $this->getOperationFromPathItem($pathItem, $method);
                if ($operation !== null && $operation->operationId !== null) {
                    return $operation->operationId;
                }
            }
        }

        return null;
    }

    private function getOperationFromPathItem(PathItem $pathItem, string $method): ?Operation
    {
        return match ($method) {
            'get' => $pathItem->get,
            'post' => $pathItem->post,
            'put' => $pathItem->put,
            'delete' => $pathItem->delete,
            'patch' => $pathItem->patch,
            'options' => $pathItem->options,
            'head' => $pathItem->head,
            'trace' => $pathItem->trace,
            default => null,
        };
    }

    private function matchPath(string $pattern, string $path): bool
    {
        // Convert OpenAPI path pattern to regex
        $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $pattern);
        if ($regex === null) {
            return false;
        }

        $regex = '#^' . $regex . '$#';

        return preg_match($regex, $path) === 1;
    }
}
