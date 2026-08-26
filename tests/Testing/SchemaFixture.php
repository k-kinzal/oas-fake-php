<?php

declare(strict_types=1);

namespace OasFake\Testing;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use OasFake\Schema;
use RuntimeException;

/**
 * Loads test schemas while treating invalid fixtures as test-infrastructure failures.
 */
final class SchemaFixture
{
    /**
     * Load a valid schema fixture from disk.
     *
     * @throws RuntimeException when the fixture is invalid
     */
    public static function fromFile(string $path): Schema
    {
        try {
            return Schema::fromFile($path);
        } catch (IOException|TypeErrorException|UnresolvableReferenceException|InvalidJsonPointerSyntaxException $exception) {
            throw new RuntimeException('Invalid OpenAPI test fixture: ' . $path, 0, $exception);
        }
    }

    /**
     * Load a valid inline schema fixture.
     *
     * @throws RuntimeException when the fixture is invalid
     */
    public static function fromString(string $content, string $format = 'yaml'): Schema
    {
        try {
            return Schema::fromString($content, $format);
        } catch (TypeErrorException $exception) {
            throw new RuntimeException('Invalid inline OpenAPI test fixture.', 0, $exception);
        }
    }
}
