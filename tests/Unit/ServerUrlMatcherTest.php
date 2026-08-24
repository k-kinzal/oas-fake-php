<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\ServerUrlMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerUrlMatcher::class)]
final class ServerUrlMatcherTest extends TestCase
{
    public function testMatchesRequestChecksOriginAndDefaultPort(): void
    {
        $request = new ServerRequest('GET', 'https://api.example.com/pets');

        self::assertTrue((new ServerUrlMatcher())->matchesRequest($request, ['scheme' => 'https', 'host' => 'api.example.com', 'port' => 443]));
    }

    public function testSpecificityReturnsMatchedBasePathLength(): void
    {
        self::assertSame(3, (new ServerUrlMatcher())->specificity('https://api.example.com/v1/pets', 'https://api.example.com/v1'));
        self::assertNull((new ServerUrlMatcher())->specificity('https://api.example.com/v10/pets', 'https://api.example.com/v1'));
    }

    public function testEffectivePortUsesExplicitAndSchemeDefaults(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(8443, $matcher->effectivePort(['scheme' => 'https', 'port' => 8443]));
        self::assertSame(80, $matcher->effectivePort(['scheme' => 'http']));
    }

    public function testEffectiveRequestPortUsesSchemeDefault(): void
    {
        self::assertSame(443, (new ServerUrlMatcher())->effectiveRequestPort(new ServerRequest('GET', 'https://api.example.com/pets')));
    }

    public function testPathPrefixSpecificityRespectsSegments(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(3, $matcher->pathPrefixSpecificity('/v1/pets', '/v1'));
        self::assertNull($matcher->pathPrefixSpecificity('/v10/pets', '/v1'));
    }

    public function testStripBasePathReturnsOperationPath(): void
    {
        self::assertSame('/pets', (new ServerUrlMatcher())->stripBasePath('/v1/pets', '/v1'));
    }

    public function testNormalizePathFoldsSlashes(): void
    {
        self::assertSame('/v1/pets', (new ServerUrlMatcher())->normalizePath('v1/pets/'));
    }
}
