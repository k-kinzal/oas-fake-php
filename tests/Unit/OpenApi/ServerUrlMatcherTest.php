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

        self::assertTrue((new ServerUrlMatcher())->matchesRequest($request, ['scheme' => 'HTTPS', 'host' => 'API.EXAMPLE.COM', 'port' => 443]));
    }

    public function testMatchesRequestRejectsDifferentOriginOrPort(): void
    {
        $request = new ServerRequest('GET', 'https://api.example.com/pets');
        $matcher = new ServerUrlMatcher();

        self::assertFalse($matcher->matchesRequest($request, ['scheme' => 'http']));
        self::assertFalse($matcher->matchesRequest($request, ['host' => 'other.example.com']));
        self::assertFalse($matcher->matchesRequest($request, ['port' => 8443]));
        self::assertTrue($matcher->matchesRequest($request, []));
    }

    public function testSpecificityReturnsMatchedBasePathLength(): void
    {
        self::assertSame(3, (new ServerUrlMatcher())->specificity('https://api.example.com/v1/pets', 'HTTPS://API.EXAMPLE.COM/v1'));
        self::assertSame(3, (new ServerUrlMatcher())->specificity('https://api.example.com/v1/pets', '/v1'));
        self::assertNull((new ServerUrlMatcher())->specificity('https://api.example.com/v10/pets', 'https://api.example.com/v1'));
    }

    public function testSpecificityRejectsDifferentOriginAndAcceptsRoot(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(0, $matcher->specificity('https://api.example.com/pets', '/'));
        self::assertNull($matcher->specificity('http://api.example.com/pets', 'https://api.example.com'));
        self::assertNull($matcher->specificity('https://other.example.com/pets', 'https://api.example.com'));
        self::assertNull($matcher->specificity('https://api.example.com:8443/pets', 'https://api.example.com'));
        self::assertNull($matcher->specificity('https://api.example.com/pets', 'http://'));
    }

    public function testEffectivePortUsesExplicitAndSchemeDefaults(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(8443, $matcher->effectivePort(['scheme' => 'https', 'port' => 8443]));
        self::assertSame(8443, $matcher->effectivePort(['scheme' => 'https', 'port' => '8443']));
        self::assertSame(80, $matcher->effectivePort(['scheme' => 'http']));
        self::assertSame(443, $matcher->effectivePort(['scheme' => 'HTTPS']));
        self::assertNull($matcher->effectivePort([]));
    }

    public function testEffectiveRequestPortUsesSchemeDefault(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(443, $matcher->effectiveRequestPort(new ServerRequest('GET', 'https://api.example.com/pets')));
        self::assertSame(80, $matcher->effectiveRequestPort(new ServerRequest('GET', 'http://api.example.com/pets')));
        self::assertSame(8443, $matcher->effectiveRequestPort(new ServerRequest('GET', 'https://api.example.com:8443/pets')));
        self::assertNull($matcher->effectiveRequestPort(new ServerRequest('GET', '/pets')));
    }

    public function testPathPrefixSpecificityRespectsSegments(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame(3, $matcher->pathPrefixSpecificity('/v1/pets', '/v1'));
        self::assertNull($matcher->pathPrefixSpecificity('/v10/pets', '/v1'));
        self::assertSame(0, $matcher->pathPrefixSpecificity('/pets', '/'));
        self::assertSame(3, $matcher->pathPrefixSpecificity('/v1', '/v1'));
    }

    public function testStripBasePathReturnsOperationPath(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame('/pets', $matcher->stripBasePath('/v1/pets', '/v1'));
        self::assertSame('/pets', $matcher->stripBasePath('/pets', '/'));
        self::assertSame('/', $matcher->stripBasePath('/v1', '/v1'));
        self::assertNull($matcher->stripBasePath('/v10/pets', '/v1'));
    }

    public function testNormalizePathFoldsSlashes(): void
    {
        $matcher = new ServerUrlMatcher();

        self::assertSame('/v1/pets', $matcher->normalizePath('v1/pets/'));
        self::assertSame('/', $matcher->normalizePath('/'));
    }
}
