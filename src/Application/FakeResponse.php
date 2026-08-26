<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use GuzzleHttp\Psr7\Response;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use OasFake\Exception\OperationNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoResponse;

/**
 * Generates fake HTTP responses from an OpenAPI schema definition.
 *
 * @visibility public
 *
 * @example Generating a response by operation ID
 *     $schema = \OasFake\Schema::fromString('{"openapi":"3.0.0","info":{"title":"Pets","version":"1"},"paths":{"/pets":{"get":{"operationId":"listPets","responses":{"200":{"description":"ok","content":{"application/json":{"schema":{"type":"object","properties":{"id":{"type":"integer"}}}}}}}}}}}');
 *     $response = \OasFake\FakeResponse::for($schema, 'listPets');
 *     $response->statusCode() // => 200
 */
final class FakeResponse
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        private int $statusCode,
        private array $headers,
        private string $rawBody,
    ) {
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     *
     * @throws JsonException when the generated response body cannot be encoded
     * @throws IOException when a schema file cannot be read
     * @throws TypeErrorException when a schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws NoPath when the OpenAPI path cannot be generated
     * @throws NoResponse when the OpenAPI response cannot be generated
     */
    public static function for(Server|Schema|FakeDataContext $source, string $operationId, ?int $statusCode = null, array $options = []): self
    {
        $context = (new FakeDataContextResolver())->resolve($source, $options);
        $definition = $context->operationLookup()->findByOperationId($operationId);

        if ($definition === null) {
            throw OperationNotFoundException::forOperationId($operationId);
        }

        $statusCode ??= (new OperationResponseResolver())->defaultStatusCode($definition);
        $response = self::generateResponse($context, $definition->pathPattern, $definition->method, $statusCode);

        return self::fromPsr7($response);
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     *
     * @throws JsonException when the generated response body cannot be encoded
     * @throws IOException when a schema file cannot be read
     * @throws TypeErrorException when a schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     * @throws NoPath when the OpenAPI path cannot be generated
     * @throws NoResponse when the OpenAPI response cannot be generated
     */
    public static function forPath(Server|Schema|FakeDataContext $source, string $path, string $method, ?int $statusCode = null, array $options = []): self
    {
        $context = (new FakeDataContextResolver())->resolve($source, $options);
        $definition = $context->operationLookup()->findByPathAndMethod($path, $method);

        if ($definition === null) {
            throw OperationNotFoundException::forPathAndMethod($path, $method);
        }

        $statusCode ??= (new OperationResponseResolver())->defaultStatusCode($definition);
        $response = self::generateResponse($context, $definition->pathPattern, $definition->method, $statusCode);

        return self::fromPsr7($response);
    }

    /**
     * Return the HTTP status code.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return the response headers.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Return the raw response body as a string.
     */
    public function body(): string
    {
        return $this->rawBody;
    }

    /**
     * Decode the response body as JSON.
     *
     * @throws JsonException when the generated body is not valid JSON
     */
    public function json(): mixed
    {
        return json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert to a PSR-7 response.
     */
    public function toPsr7(): ResponseInterface
    {
        return new Response($this->statusCode, $this->headers, $this->rawBody);
    }

    /**
     * @return array{statusCode: int, headers: array<string, string>, body: string}
     */
    public function toArray(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->rawBody,
        ];
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     *
     * @throws JsonException when the generated response body cannot be encoded
     * @throws NoPath when the OpenAPI path cannot be generated
     * @throws NoResponse when the OpenAPI response cannot be generated
     */
    public static function generateResponse(Schema|FakeDataContext $source, string $path, string $method, int $statusCode = 200, array $options = []): ResponseInterface
    {
        $context = $source instanceof FakeDataContext ? $source : new FakeDataContext($source, $options);

        return (new FakeResponseFactory())->create($context, $path, $method, $statusCode);
    }

    /**
     * Create a fake response value from a PSR-7 response.
     */
    private static function fromPsr7(ResponseInterface $response): self
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }

        return new self(
            $response->getStatusCode(),
            $headers,
            (string) $response->getBody(),
        );
    }
}
