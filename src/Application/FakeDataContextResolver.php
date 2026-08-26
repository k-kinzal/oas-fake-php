<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;

/**
 * Normalizes supported fake-data sources into one generation context.
 *
 * @visibility namespace
 */
final class FakeDataContextResolver
{
    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     *
     * @throws IOException when a configured schema file cannot be read
     * @throws TypeErrorException when a configured schema has an invalid structure
     * @throws UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws InvalidJsonPointerSyntaxException when a JSON pointer is invalid
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
