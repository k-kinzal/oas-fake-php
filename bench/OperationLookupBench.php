<?php

declare(strict_types=1);

namespace OasFake\Bench;

use JsonException;
use LogicException;
use OasFake\OperationDefinition;
use OasFake\OperationLookup;
use OasFake\Schema;
use PhpBench\Attributes as Bench;

/**
 * Measures warm operation-id and request-path lookup separately from schema parsing.
 *
 * @visibility namespace
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\ParamProviders('provideSchemaSizes')]
final class OperationLookupBench
{
    private ?OperationLookup $lookup = null;

    /**
     * @param array{operations: int} $params
     *
     * @throws JsonException when the benchmark schema cannot be encoded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the benchmark schema is invalid
     *
     * @mutation $this
     */
    public function setUp(array $params): void
    {
        $paths = [];
        foreach (range(1, $params['operations']) as $index) {
            $paths['/resources/' . $index . '/{id}'] = [
                'get' => ['operationId' => 'resource' . $index, 'responses' => ['200' => ['description' => 'ok']]],
            ];
        }
        $this->lookup = new OperationLookup(Schema::fromString(json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Lookup workload', 'version' => '1'],
            'paths' => $paths,
        ], JSON_THROW_ON_ERROR), 'json'));
    }

    /**
     * @return iterable<string, array{operations: int}>
     */
    public function provideSchemaSizes(): iterable
    {
        yield '100 operations' => ['operations' => 100];
        yield '1000 operations' => ['operations' => 1000];
    }

    /**
     * @param array{operations: int} $params
     *
     * @throws LogicException when the benchmark setup hook was not run
     */
    #[Bench\Revs(1000000)]
    public function benchIndexedOperation(array $params): ?OperationDefinition
    {
        $lookup = $this->lookup ?? throw new LogicException('Run the benchmark setup before measuring lookup.');

        return $lookup->findByOperationId('resource' . $params['operations']);
    }

    /**
     * @param array{operations: int} $params
     *
     * @throws LogicException when the benchmark setup hook was not run
     */
    #[Bench\Revs(100)]
    public function benchTemplatedRequestPath(array $params): ?OperationDefinition
    {
        $lookup = $this->lookup ?? throw new LogicException('Run the benchmark setup before measuring lookup.');

        return $lookup->findByRequestPathAndMethod('/resources/' . $params['operations'] . '/42', 'GET');
    }
}
