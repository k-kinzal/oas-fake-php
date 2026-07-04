<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function is_array;
use function is_scalar;
use function json_decode;
use function json_encode;

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
        $fakeData = $context->mockResponse($path, $method, $statusCode);

        return self::buildResponse($fakeData, $statusCode);
    }

    private static function buildResponse(mixed $data, int $statusCode): ResponseInterface
    {
        if (is_array($data) || is_scalar($data) || $data === null) {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } else {
            $body = json_encode(null, JSON_THROW_ON_ERROR);
        }

        return new Response($statusCode, ['Content-Type' => 'application/json'], $body);
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
