<?php

declare(strict_types=1);

namespace OasFake\Tests\Doctest;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * Runs source PHPDoc examples on PHPUnit 9.
 */
final class DocumentationTest extends LegacyDoctestRunner
{
    /**
     * Scan the production API documentation for executable examples.
     */
    public static function configure(): Configuration
    {
        return new Configuration(directories: [__DIR__ . '/../../src']);
    }
}
