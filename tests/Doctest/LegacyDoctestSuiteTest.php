<?php

declare(strict_types=1);

namespace OasFake\Tests\Doctest;

use Override;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * @medium
 */
final class LegacyDoctestSuiteTest extends LegacyDoctestRunner
{
    /**
     * Selects the production autoload roots whose PHPDoc examples are executable.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return new Configuration(directories: [__DIR__ . '/../../src']);
    }
}
