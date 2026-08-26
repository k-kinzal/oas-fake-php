<?php

declare(strict_types=1);

namespace OasFake\Testing;

use OasFake\OperationLookup;
use OasFake\Schema;
use OasFake\Validator;

/**
 * Provides the canonical petstore schema to tests that exercise public behavior.
 */
final class Petstore
{
    /**
     * Return the fixture file path.
     */
    public static function path(): string
    {
        return __DIR__ . '/../Fixtures/openapi/petstore.yaml';
    }

    /**
     * Load a fresh schema so tests do not share mutable OpenAPI objects.
     */
    public static function schema(): Schema
    {
        return SchemaFixture::fromFile(self::path());
    }

    /**
     * Build an operation lookup for a fresh schema.
     */
    public static function lookup(): OperationLookup
    {
        return new OperationLookup(self::schema());
    }

    /**
     * Build a validator for a fresh schema.
     */
    public static function validator(): Validator
    {
        return new Validator(self::schema());
    }
}
