<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Schema as CebeSchema;
use Vural\OpenAPIFaker\Exception\NoPath;
use Vural\OpenAPIFaker\Exception\NoRequest;
use Vural\OpenAPIFaker\Exception\NoResponse;
use Vural\OpenAPIFaker\OpenAPIFaker;
use Vural\OpenAPIFaker\Options;
use Vural\OpenAPIFaker\SchemaFaker\SchemaFaker;

/**
 * Shared fake-data generation context for one schema and faker option set.
 *
 * @visibility public
 *
 * @example Sharing one schema across fake-data operations
 *     $schema = \OasFake\Schema::fromString('{"openapi":"3.0.0","info":{"title":"Pets","version":"1"},"paths":{}}');
 *     $fakeData = new \OasFake\FakeDataContext($schema);
 *     $fakeData->schema() === $schema // => true
 */
final class FakeDataContext
{
    private OperationLookup $operationLookup;

    private OpenAPIFaker $openApiFaker;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     */
    public function __construct(private Schema $schema, private array $fakerOptions = [])
    {
        $this->operationLookup = new OperationLookup($schema);
        $this->openApiFaker = OpenAPIFaker::createFromSchema($schema->openApi());

        if ($fakerOptions !== []) {
            $this->openApiFaker->setOptions($fakerOptions);
        }
    }

    /**
     * Return the schema used by this context.
     */
    public function schema(): Schema
    {
        return $this->schema;
    }

    /**
     * Return the operation lookup index for this schema.
     */
    public function operationLookup(): OperationLookup
    {
        return $this->operationLookup;
    }

    /**
     * Return the faker options used by this context.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array
    {
        return $this->fakerOptions;
    }

    /**
     * Generate fake request data for an operation path and method.
     *
     * @throws NoPath when the path does not exist in the schema
     * @throws NoRequest when the operation has no request definition
     */
    public function mockRequest(string $path, string $method): mixed
    {
        return $this->openApiFaker->mockRequest($path, $method);
    }

    /**
     * Generate fake response data for an operation path, method, and status.
     *
     * @throws NoPath when the path does not exist in the schema
     * @throws NoResponse when the operation has no response for the status
     */
    public function mockResponse(string $path, string $method, int $statusCode): mixed
    {
        return $this->openApiFaker->mockResponse($path, $method, (string) $statusCode);
    }

    /**
     * Generate fake data directly from a schema object.
     */
    public function mockSchema(CebeSchema $schema): mixed
    {
        $options = new Options();

        if (isset($this->fakerOptions['alwaysFakeOptionals'])) {
            $options->setAlwaysFakeOptionals($this->fakerOptions['alwaysFakeOptionals']);
        }

        if (isset($this->fakerOptions['minItems'])) {
            $options->setMinItems($this->fakerOptions['minItems']);
        }

        if (isset($this->fakerOptions['maxItems'])) {
            $options->setMaxItems($this->fakerOptions['maxItems']);
        }

        return (new SchemaFaker($schema, $options))->generate();
    }
}
