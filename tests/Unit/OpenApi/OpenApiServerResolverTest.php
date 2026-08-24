<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Server;
use OasFake\OpenApiServerResolver;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class OpenApiServerResolverTest extends TestCase
{
    public function testAllReturnsEffectiveOperationUrls(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        self::assertSame(['https://api.petstore.example.com'], (new OpenApiServerResolver())->all($schema->openApi()));
    }

    public function testEffectiveFallsBackFromOperationToDocument(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        self::assertSame(['https://api.petstore.example.com'], (new OpenApiServerResolver())->effective($schema->openApi()));
    }

    public function testEffectiveDefaultsToRootWithoutDeclaredServers(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'JSON'
            {
                "openapi": "3.0.0",
                "info": {"title": "No Servers", "version": "1.0.0"},
                "paths": {}
            }
            JSON, 'json');
        $resolver = new OpenApiServerResolver();

        self::assertSame(['/'], $resolver->effective($schema->openApi()));
        self::assertSame(['/'], $resolver->all($schema->openApi()));
    }

    public function testEffectiveHonorsOperationPathAndDocumentPrecedence(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'JSON'
            {
                "openapi": "3.0.0",
                "info": {"title": "Servers", "version": "1.0.0"},
                "servers": [{"url": "https://document.example.com"}],
                "paths": {
                    "/pets": {
                        "servers": [{"url": "https://path.example.com"}],
                        "get": {
                            "servers": [{"url": "https://operation.example.com"}],
                            "responses": {"200": {"description": "Successful"}}
                        },
                        "post": {"responses": {"201": {"description": "Created"}}}
                    },
                    "/status": {
                        "get": {"responses": {"200": {"description": "Successful"}}}
                    }
                }
            }
            JSON, 'json');
        $openApi = $schema->openApi();
        self::assertNotNull($openApi->paths);
        $pets = $openApi->paths->getPath('/pets');
        $status = $openApi->paths->getPath('/status');
        self::assertNotNull($pets);
        self::assertNotNull($status);
        self::assertNotNull($pets->get);
        self::assertNotNull($pets->post);
        self::assertNotNull($status->get);
        $resolver = new OpenApiServerResolver();

        self::assertSame(['https://operation.example.com'], $resolver->effective($openApi, $pets, $pets->get));
        self::assertSame(['https://path.example.com'], $resolver->effective($openApi, $pets, $pets->post));
        self::assertSame(['https://document.example.com'], $resolver->effective($openApi, $status, $status->get));
        self::assertSame(
            ['https://operation.example.com', 'https://path.example.com', 'https://document.example.com'],
            $resolver->all($openApi),
        );
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the server fixture is invalid
     */
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
        self::assertSame(['/'], (new OpenApiServerResolver())->substituteVariables([]));
    }
}
