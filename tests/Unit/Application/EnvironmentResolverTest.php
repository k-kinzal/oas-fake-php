<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\EnvironmentResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\EnvironmentResolver
 */
#[CoversClass(EnvironmentResolver::class)]
final class EnvironmentResolverTest extends TestCase
{
    public function testStringUsesDefaultForMissingOrEmptyValue(): void
    {
        putenv('OAS_FAKE_TEST_STRING');
        $resolver = new EnvironmentResolver();

        self::assertSame('fallback', $resolver->string('OAS_FAKE_TEST_STRING', 'fallback'));

        putenv('OAS_FAKE_TEST_STRING=');

        try {
            self::assertSame('fallback', $resolver->string('OAS_FAKE_TEST_STRING', 'fallback'));
        } finally {
            putenv('OAS_FAKE_TEST_STRING');
        }
    }

    public function testStringReturnsConfiguredValue(): void
    {
        putenv('OAS_FAKE_TEST_STRING=configured');

        try {
            self::assertSame('configured', (new EnvironmentResolver())->string('OAS_FAKE_TEST_STRING', 'fallback'));
        } finally {
            putenv('OAS_FAKE_TEST_STRING');
        }
    }

    public function testBooleanParsesEnvironmentValue(): void
    {
        putenv('OAS_FAKE_TEST_BOOLEAN=false');

        self::assertFalse((new EnvironmentResolver())->boolean('OAS_FAKE_TEST_BOOLEAN', true));

        putenv('OAS_FAKE_TEST_BOOLEAN');
    }

    public function testBooleanUsesDefaultForMissingOrEmptyValue(): void
    {
        putenv('OAS_FAKE_TEST_BOOLEAN');
        $resolver = new EnvironmentResolver();

        self::assertTrue($resolver->boolean('OAS_FAKE_TEST_BOOLEAN', true));
        self::assertFalse($resolver->boolean('OAS_FAKE_TEST_BOOLEAN', false));

        putenv('OAS_FAKE_TEST_BOOLEAN=');

        try {
            self::assertTrue($resolver->boolean('OAS_FAKE_TEST_BOOLEAN', true));
            self::assertFalse($resolver->boolean('OAS_FAKE_TEST_BOOLEAN', false));
        } finally {
            putenv('OAS_FAKE_TEST_BOOLEAN');
        }
    }
}
