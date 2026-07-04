<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use OasFake\Exception\SchemaNotFoundException;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema::class)]
final class SchemaTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../Fixtures/openapi';
    }

    public function testFromFileLoadsYaml(): void
    {
        $schema = Schema::fromFile($this->fixturesPath . '/petstore.yaml');

        self::assertInstanceOf(OpenApi::class, $schema->openApi());
        self::assertSame('Petstore API', $schema->openApi()->info->title);
    }

    public function testFromFileLoadsRelativePath(): void
    {
        $schema = Schema::fromFile('tests/Fixtures/openapi/petstore.yaml');

        self::assertInstanceOf(OpenApi::class, $schema->openApi());
        self::assertSame('Petstore API', $schema->openApi()->info->title);
    }

    public function testFromFileThrowsForNonExistentFile(): void
    {
        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage('OpenAPI schema file not found');

        Schema::fromFile('/nonexistent/path/schema.yaml');
    }

    public function testFromStringYaml(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test API\n  version: 1.0.0\npaths: {}";

        $schema = Schema::fromString($yaml);

        self::assertSame('Test API', $schema->openApi()->info->title);
    }

    public function testFromStringJson(): void
    {
        $json = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'JSON API', 'version' => '1.0.0'],
            'paths' => (object) [],
        ], JSON_THROW_ON_ERROR);

        $schema = Schema::fromString($json, 'json');

        self::assertSame('JSON API', $schema->openApi()->info->title);
    }

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
        $schema = Schema::fromString($yaml);

        $result = $schema->openApi();

        self::assertInstanceOf(OpenApi::class, $result);
    }

    public function testServerUrlsReturnsUrls(): void
    {
        $schema = Schema::fromFile($this->fixturesPath . '/petstore.yaml');

        $urls = $schema->serverUrls();

        self::assertSame(['https://api.petstore.example.com'], $urls);
    }

    public function testServerUrlsReturnsSlashWhenNoServers(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";
        $schema = Schema::fromString($yaml);

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
        $schema = Schema::fromString($yaml);

        $urls = $schema->serverUrls();

        self::assertSame(['https://api.example.com'], $urls);
    }

    public function testServerUrlsUsesPathLevelServersForPathOperations(): void
    {
        $schema = Schema::fromString(<<<'YAML'
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
        $schema = Schema::fromString(<<<'YAML'
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
