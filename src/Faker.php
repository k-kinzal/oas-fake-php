<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;

use function is_array;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseInterface;

use function strtoupper;

use Vural\OpenAPIFaker\OpenAPIFaker;

final class Faker
{
    private readonly OpenAPIFaker $openApiFaker;

    /** @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options */
    public function __construct(
        private readonly Schema $schema,
        private readonly array $options = [],
    ) {
        $this->openApiFaker = OpenAPIFaker::createFromSchema($this->schema->openApi());

        if ($this->options !== []) {
            $this->openApiFaker->setOptions($this->options);
        }
    }

    public function response(OperationAddress $operation, int $statusCode = 200): ResponseInterface
    {
        return $this->responseForPath($operation->path(), $operation->method(), $statusCode);
    }

    public function responseForPath(string $path, string $method, int $statusCode = 200): ResponseInterface
    {
        $fakeData = $this->openApiFaker->mockResponse($path, strtoupper($method), (string) $statusCode);

        return $this->buildResponse($fakeData, $statusCode);
    }

    private function buildResponse(mixed $data, int $statusCode): ResponseInterface
    {
        if (is_array($data) || is_scalar($data) || $data === null) {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } else {
            $body = json_encode(null, JSON_THROW_ON_ERROR);
        }

        return new Response($statusCode, ['Content-Type' => 'application/json'], $body);
    }
}
