<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use JsonException;
use OasFake\Exception\SchemaNotFoundException;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @covers \OasFake\Schema
 *
 * @uses \OasFake\Exception\SchemaNotFoundException
 * @uses \OasFake\OpenApiServerResolver
 */
#[CoversClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SchemaNotFoundException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
final class SchemaTest extends TestCase
{
    public function testFromStringPreservesParserFailureContract(): void
    {
        $this->expectException(ParseException::class);

        \OasFake\Testing\SchemaFixture::fromString('openapi: [invalid');
    }

    public function testFromFileLoadsYaml(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi' . '/petstore.yaml');

        self::assertSame('Petstore API', $schema->openApi()->info->title);
    }

    public function testFromFileLoadsRelativePath(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile('tests/Fixtures/openapi/petstore.yaml');

        self::assertSame('Petstore API', $schema->openApi()->info->title);
    }

    public function testFromFileThrowsForNonExistentFile(): void
    {
        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage('OpenAPI schema file not found');

        \OasFake\Testing\SchemaFixture::fromFile('/nonexistent/path/schema.yaml');
    }

    public function testFromStringYaml(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test API\n  version: 1.0.0\npaths: {}";

        $schema = \OasFake\Testing\SchemaFixture::fromString($yaml);

        self::assertSame('Test API', $schema->openApi()->info->title);
    }

    /**
     * @throws JsonException when the fixture cannot be encoded
     */
    public function testFromStringJson(): void
    {
        $json = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'JSON API', 'version' => '1.0.0'],
            'paths' => (object) [],
        ], JSON_THROW_ON_ERROR);

        $schema = \OasFake\Testing\SchemaFixture::fromString($json, 'json');

        self::assertSame('JSON API', $schema->openApi()->info->title);
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when the OpenAPI fixture is invalid
     */
    public function testFromOpenApi(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Direct API', 'version' => '1.0.0'],
            'paths' => [],
        ]);

        $schema = Schema::fromOpenApi($openApi);

        self::assertSame($openApi, $schema->openApi());
    }

    public function testOpenApiReturnsSpec(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";
        $schema = \OasFake\Testing\SchemaFixture::fromString($yaml);

        $result = $schema->openApi();

        self::assertSame('Test', $result->info->title);
    }

    public function testServerUrlsReturnsUrls(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi' . '/petstore.yaml');

        $urls = $schema->serverUrls();

        self::assertSame(['https://api.petstore.example.com'], $urls);
    }

    public function testServerUrlsReturnsSlashWhenNoServers(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";
        $schema = \OasFake\Testing\SchemaFixture::fromString($yaml);

        $urls = $schema->serverUrls();

        self::assertSame(['/'], $urls);
    }

    public function testServerUrlsResolvesTemplateVariables(): void
    {
        $yaml = <<<'YAML'
            openapi: 3.0.0
            info:
              title: Test
              version: 1.0.0
            servers:
              - url: https://{env}.example.com
                variables:
                  env:
                    default: api
            paths: {}
            YAML;
        $schema = \OasFake\Testing\SchemaFixture::fromString($yaml);

        $urls = $schema->serverUrls();

        self::assertSame(['https://api.example.com'], $urls);
    }

    public function testServerUrlsUsesPathLevelServersForPathOperations(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Path Server API
              version: 1.0.0
            servers:
              - url: https://root.example.com
            paths:
              /pets:
                servers:
                  - url: https://pets.example.com/v1
                get:
                  operationId: listPets
                  responses:
                    '200':
                      description: OK
              /orders:
                get:
                  operationId: listOrders
                  responses:
                    '200':
                      description: OK
            YAML);

        self::assertSame(['https://pets.example.com/v1', 'https://root.example.com'], $schema->serverUrls());
    }

    public function testEffectiveServerUrlsUsesOperationLevelBeforePathAndRoot(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromString(<<<'YAML'
            openapi: 3.0.0
            info:
              title: Operation Server API
              version: 1.0.0
            servers:
              - url: https://root.example.com
            paths:
              /pets:
                servers:
                  - url: https://path.example.com/v1
                get:
                  operationId: listPets
                  servers:
                    - url: https://operation.example.com/v2
                  responses:
                    '200':
                      description: OK
            YAML);

        $operation = $schema->openApi()->paths['/pets']->get;

        self::assertSame(
            ['https://operation.example.com/v2'],
            $schema->effectiveServerUrls($schema->openApi()->paths['/pets'], $operation),
        );
        self::assertSame(['https://operation.example.com/v2'], $schema->serverUrls());
    }
}
