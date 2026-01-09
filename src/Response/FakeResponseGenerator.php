<?php

declare(strict_types=1);

namespace OasFakePHP\Response;

use cebe\openapi\spec\OpenApi;
use GuzzleHttp\Psr7\Response;

use function is_array;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseInterface;

use function strtoupper;

use Vural\OpenAPIFaker\OpenAPIFaker;

final class FakeResponseGenerator
{
    private readonly OpenAPIFaker $faker;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function __construct(
        private readonly OpenApi $schema,
        array $options = [],
    ) {
        $this->faker = OpenAPIFaker::createFromSchema($this->schema);

        if ($options !== []) {
            $this->faker->setOptions($options);
        }
    }

    public function generate(OperationAddress $operation, int $statusCode = 200): ResponseInterface
    {
        return $this->generateForPath($operation->path(), $operation->method(), $statusCode);
    }

    public function generateForPath(string $path, string $method, int $statusCode = 200): ResponseInterface
    {
        $fakeData = $this->faker->mockResponse($path, strtoupper($method), (string) $statusCode);

        return $this->buildResponse($fakeData, $statusCode);
    }

    private function buildResponse(mixed $data, int $statusCode): ResponseInterface
    {
        if (is_array($data) || is_scalar($data) || $data === null) {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } else {
            $body = json_encode(null, JSON_THROW_ON_ERROR);
        }

        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            $body,
        );
    }
}
