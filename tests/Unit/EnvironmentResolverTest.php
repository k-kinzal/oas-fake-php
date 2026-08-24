<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\EnvironmentResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvironmentResolver::class)]
final class EnvironmentResolverTest extends TestCase
{
    public function testStringUsesDefaultForMissingOrEmptyValue(): void
    {
        putenv('OAS_FAKE_TEST_STRING');

        self::assertSame('fallback', (new EnvironmentResolver())->string('OAS_FAKE_TEST_STRING', 'fallback'));
    }

    public function testBooleanParsesEnvironmentValue(): void
    {
        putenv('OAS_FAKE_TEST_BOOLEAN=false');

        self::assertFalse((new EnvironmentResolver())->boolean('OAS_FAKE_TEST_BOOLEAN', true));

        putenv('OAS_FAKE_TEST_BOOLEAN');
    }
}
