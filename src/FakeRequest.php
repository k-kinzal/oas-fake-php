<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use OasFake\Exception\OperationNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

use function str_replace;
use function strtoupper;

/**
 * Generates fake HTTP requests from an OpenAPI schema definition.
 */
final class FakeRequest
{
    /**
     * @param array<string, string> $pathParams
     * @param array<string, list<string>|string> $queryParams
     * @param array<string, string> $headerParams
     */
    private function __construct(
        private string $method,
        private string $baseUrl,
        private string $pathPattern,
        private array $pathParams,
        private array $queryParams,
        private array $headerParams,
        private ?string $rawBody,
    ) {
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public static function for(Server|Schema|FakeDataContext $source, string $operationId, array $options = []): self
    {
        $context = self::resolveSource($source, $options);
        $info = $context->operationLookup()->findByOperationId($operationId);

        if ($info === null) {
            throw OperationNotFoundException::forOperationId($operationId);
        }

        return self::buildFromInfo($context, $info);
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public static function forPath(Server|Schema|FakeDataContext $source, string $path, string $method, array $options = []): self
    {
        $context = self::resolveSource($source, $options);
        $info = $context->operationLookup()->findByPathAndMethod($path, $method);

        if ($info === null) {
            throw OperationNotFoundException::forPathAndMethod($path, $method);
        }

        return self::buildFromInfo($context, $info);
    }

    /**
     * Return the HTTP method in uppercase.
     */
    public function method(): string
    {
        return strtoupper($this->method);
    }

    /**
     * Build the full URL with path parameters and query string applied.
     */
    public function url(): string
    {
        $path = $this->pathPattern;
        foreach ($this->pathParams as $name => $value) {
            $path = str_replace('{' . $name . '}', $value, $path);
        }

        $url = rtrim($this->baseUrl, '/') . $path;

        if ($this->queryParams !== []) {
            $url .= '?' . Query::build($this->queryParams);
        }

        return $url;
    }

    /**
     * Return the raw request body.
     */
    public function body(): ?string
    {
        return $this->rawBody;
    }

    /**
     * Return the request headers.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headerParams;
    }

    /**
     * Return the path parameters.
     *
     * @return array<string, string>
     */
    public function pathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * Return the query parameters.
     *
     * @return array<string, list<string>|string>
     */
    public function queryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Return a new instance with the given path parameter overridden.
     */
    public function withPathParam(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->pathParams[$name] = $value;

        return $clone;
    }

    /**
     * Return a new instance with the given query parameter overridden.
     */
    public function withQueryParam(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->queryParams[$name] = $value;

        return $clone;
    }

    /**
     * Return a new instance with the given header overridden.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headerParams[$name] = $value;

        return $clone;
    }

    /**
     * Return a new instance with the given request body.
     */
    public function withBody(string $body): self
    {
        return new self(
            $this->method,
            $this->baseUrl,
            $this->pathPattern,
            $this->pathParams,
            $this->queryParams,
            $this->headerParams,
            $body,
        );
    }

    /**
     * Convert to a PSR-7 server request.
     */
    public function toPsr7(): ServerRequestInterface
    {
        $uri = new Uri($this->url());
        $request = new ServerRequest(strtoupper($this->method), $uri, $this->headerParams, $this->rawBody);

        if ($this->queryParams !== []) {
            $request = $request->withQueryParams($this->queryParams);
        }

        return $request;
    }

    /**
     * Generate a cURL command string for this request.
     */
    public function toCurl(): string
    {
        $parts = ['curl'];
        $method = strtoupper($this->method);

        if ($method !== 'GET') {
            $parts[] = '-X ' . $method;
        }

        $parts[] = "'" . $this->url() . "'";

        foreach ($this->headerParams as $name => $value) {
            $parts[] = "-H '" . $name . ': ' . $value . "'";
        }

        if ($this->rawBody !== null) {
            $parts[] = "-d '" . $this->rawBody . "'";
        }

        return implode(" \\\n  ", $parts);
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: string|null}
     */
    public function toArray(): array
    {
        return [
            'method' => strtoupper($this->method),
            'url' => $this->url(),
            'headers' => $this->headerParams,
            'body' => $this->rawBody,
        ];
    }

    private static function buildFromInfo(FakeDataContext $context, OperationInfo $info): self
    {
        $paramFaker = new ParameterFaker($context->fakerOptions());
        $params = $paramFaker->generate($info->parameters);

        $body = null;
        $headers = $params['header'];

        if ($info->operation->requestBody !== null) {
            $fakeData = $context->mockRequest($info->pathPattern, $info->method);

            if (is_array($fakeData) || $fakeData !== null) {
                $body = json_encode($fakeData, JSON_THROW_ON_ERROR);
                $headers['Content-Type'] ??= 'application/json';
            }
        }

        $baseUrl = $info->serverUrls[0] ?? '/';

        return new self(
            method: $info->method,
            baseUrl: $baseUrl,
            pathPattern: $info->pathPattern,
            pathParams: $params['path'],
            queryParams: $params['query'],
            headerParams: $headers,
            rawBody: $body,
        );
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    private static function resolveSource(Server|Schema|FakeDataContext $source, array $options): FakeDataContext
    {
        if ($source instanceof FakeDataContext) {
            return $source;
        }

        if ($source instanceof Server) {
            $schema = $source->schema();
            $fakerOptions = $options !== [] ? $options : $source->fakerOptions();

            return new FakeDataContext($schema, $fakerOptions);
        }

        return new FakeDataContext($source, $options);
    }
}
