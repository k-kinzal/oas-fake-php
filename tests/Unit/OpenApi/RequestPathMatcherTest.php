<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\RequestPathMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\RequestPathMatcher
 */
#[CoversClass(RequestPathMatcher::class)]
final class RequestPathMatcherTest extends TestCase
{
    public function testMatchesOpenApiTemplateSegments(): void
    {
        $matcher = new RequestPathMatcher();

        self::assertTrue($matcher->matches('/pets/{petId}', '/pets/123'));
        self::assertFalse($matcher->matches('/pets/{petId}', '/pets/123/owner'));
    }

    public function testMatchesRequiresTheEntireLiteralPathIncludingTrailingBytes(): void
    {
        $matcher = new RequestPathMatcher();

        self::assertTrue($matcher->matches('/health', '/health'));
        self::assertFalse($matcher->matches('/health', "/health\n"));
        self::assertFalse($matcher->matches('/pets/{id}/owner', "/pets/1/owner\n"));
    }
}
