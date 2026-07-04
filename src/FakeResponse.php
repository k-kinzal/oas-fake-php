<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Response as CebeResponse;
use cebe\openapi\spec\Schema as CebeSchema;
use GuzzleHttp\Psr7\Response;

use function is_int;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

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
    private function __construct(
        private int $statusCode,
        private array $headers,
        private string $rawBody,
    ) {
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public static function for(Server|Schema|FakeDataContext $source, string $operationId, ?int $statusCode = null, array $options = []): self
    {
        $context = self::resolveSource($source, $options);
        $info = $context->operationLookup()->findByOperationId($operationId);

        if ($info === null) {
            throw OperationNotFoundException::forOperationId($operationId);
        }

        $statusCode ??= self::defaultStatusCode($info);
        $response = self::generateResponse($context, $info->pathPattern, $info->method, $statusCode);

        return self::fromPsr7($response);
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public static function forPath(Server|Schema|FakeDataContext $source, string $path, string $method, ?int $statusCode = null, array $options = []): self
    {
        $context = self::resolveSource($source, $options);
        $info = $context->operationLookup()->findByPathAndMethod($path, $method);

        if ($info === null) {
            throw OperationNotFoundException::forPathAndMethod($path, $method);
        }

        $statusCode ??= self::defaultStatusCode($info);
        $response = self::generateResponse($context, $info->pathPattern, $info->method, $statusCode);

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
        $operationInfo = $context->operationLookup()->findByPathAndMethod($path, $method);
        $mediaType = $operationInfo === null ? 'application/json' : self::responseMediaType($operationInfo, $statusCode);
        $fakeData = self::responseData($context, $operationInfo, $path, $method, $statusCode, $mediaType);

        return self::buildResponse($fakeData, $statusCode, $mediaType);
    }

    private static function buildResponse(mixed $data, int $statusCode, string $mediaType): ResponseInterface
    {
        return new Response($statusCode, ['Content-Type' => $mediaType], PayloadSerializer::serialize($data, $mediaType));
    }

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

    private static function defaultStatusCode(OperationInfo $operationInfo): int
    {
        if ($operationInfo->operation->responses !== null) {
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

    private static function responseMediaType(OperationInfo $operationInfo, int $statusCode): string
    {
        $response = self::responseForStatus($operationInfo, $statusCode);

        if ($response === null || $response->content === null || $response->content === []) {
            return 'application/json';
        }

        $mediaTypes = [];
        foreach ($response->content as $mediaType => $_content) {
            if (is_int($mediaType) || is_string($mediaType)) {
                $mediaTypes[] = (string) $mediaType;
            }
        }

        return PayloadSerializer::preferredMediaType($mediaTypes);
    }

    private static function responseData(
        FakeDataContext $context,
        ?OperationInfo $operationInfo,
        string $path,
        string $method,
        int $statusCode,
        string $mediaType,
    ): mixed {
        $schema = $operationInfo === null ? null : self::responseSchema($operationInfo, $statusCode, $mediaType);
        if ($schema instanceof CebeSchema && !PayloadSerializer::isJsonMediaType($mediaType)) {
            return $context->mockSchema($schema);
        }

        return $context->mockResponse($path, $method, $statusCode);
    }

    private static function responseSchema(OperationInfo $operationInfo, int $statusCode, string $mediaType): ?CebeSchema
    {
        $response = self::responseForStatus($operationInfo, $statusCode);
        if ($response === null || $response->content === null) {
            return null;
        }

        foreach ($response->content as $candidateMediaType => $content) {
            if ((!is_int($candidateMediaType) && !is_string($candidateMediaType)) || (string) $candidateMediaType !== $mediaType || !$content instanceof MediaType) {
                continue;
            }

            return $content->schema instanceof CebeSchema ? $content->schema : null;
        }

        return null;
    }

    private static function responseForStatus(OperationInfo $operationInfo, int $statusCode): ?CebeResponse
    {
        if ($operationInfo->operation->responses === null) {
            return null;
        }

        foreach ($operationInfo->operation->responses as $code => $response) {
            if (!is_int($code) && !is_string($code)) {
                continue;
            }

            if ((string) $code === (string) $statusCode && $response instanceof CebeResponse) {
                return $response;
            }
        }

        return null;
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
