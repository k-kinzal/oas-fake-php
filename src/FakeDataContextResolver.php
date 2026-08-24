<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Normalizes supported fake-data sources into one generation context.
 */
final class FakeDataContextResolver
{
    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function resolve(Server|Schema|FakeDataContext $source, array $options = []): FakeDataContext
    {
        if ($source instanceof FakeDataContext) {
            return $source;
        }

        if ($source instanceof Server) {
            $fakerOptions = $options !== [] ? $options : $source->fakerOptions();

            return new FakeDataContext($source->schema(), $fakerOptions);
        }

        return new FakeDataContext($source, $options);
    }
}
