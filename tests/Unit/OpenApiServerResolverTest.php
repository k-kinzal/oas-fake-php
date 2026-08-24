<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Server;
use OasFake\OpenApiServerResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiServerResolver::class)]
final class OpenApiServerResolverTest extends TestCase
{
    public function testAllReturnsEffectiveOperationUrls(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        self::assertSame(['https://api.petstore.example.com'], (new OpenApiServerResolver())->all($schema->openApi()));
    }

    public function testEffectiveFallsBackFromOperationToDocument(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        self::assertSame(['https://api.petstore.example.com'], (new OpenApiServerResolver())->effective($schema->openApi()));
    }

    public function testSubstituteVariablesUsesDeclaredDefaults(): void
    {
        $server = new Server([
            'url' => 'https://{subdomain}.example.com/{version}',
            'variables' => [
                'subdomain' => ['default' => 'api'],
                'version' => ['default' => 'v1'],
            ],
        ]);

        self::assertSame(
            ['https://api.example.com/v1'],
            (new OpenApiServerResolver())->substituteVariables([$server]),
        );
    }
}
