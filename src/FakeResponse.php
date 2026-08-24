<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use OasFake\Exception\OperationNotFoundException;
use Psr\Http\Message\ResponseInterface;

/**
 * Generates fake HTTP responses from an OpenAPI schema definition.
 */
final class FakeResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private int $statusCode,
        private array $headers,
        private string $rawBody,
    ) {
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public static function for(FakeDataSource|Schema $source, string $operationId, ?int $statusCode = null, array $options = []): self
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
     */
    public static function forPath(FakeDataSource|Schema $source, string $path, string $method, ?int $statusCode = null, array $options = []): self
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
     */
    public static function generateResponse(Schema|FakeDataContext $source, string $path, string $method, int $statusCode = 200, array $options = []): ResponseInterface
    {
        $context = $source instanceof FakeDataContext ? $source : new FakeDataContext($source, $options);

        return (new FakeResponseFactory())->create($context, $path, $method, $statusCode);
    }

    /**
     * Create a fake response value from a PSR-7 response.
     */
    public static function fromPsr7(ResponseInterface $response): self
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
