<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Supplies the schema and faker policy needed to generate fake data.
 */
interface FakeDataSource
{
    /**
     * Return the schema used for fake-data generation.
     */
    public function schema(): Schema;

    /**
     * Return the OpenAPI faker options.
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(): array;
}
