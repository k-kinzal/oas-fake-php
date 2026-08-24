<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Normalizes supported fake-data sources into one generation context.
 *
 * @visibility namespace
 */
final class FakeDataContextResolver
{
    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function resolve(FakeDataSource|Schema $source, array $options = []): FakeDataContext
    {
        if ($source instanceof FakeDataContext) {
            return $source;
        }

        if ($source instanceof Schema) {
            return new FakeDataContext($source, $options);
        }

        $fakerOptions = $options !== [] ? $options : $source->fakerOptions();

        return new FakeDataContext($source->schema(), $fakerOptions);
    }
}
