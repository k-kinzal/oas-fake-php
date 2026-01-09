<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Config;

use cebe\openapi\spec\OpenApi;
use OasFakePHP\Config\Configuration;
use OasFakePHP\Exception\SchemaNotFoundException;
use OasFakePHP\Vcr\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/openapi';
        putenv('OAS_FAKE_VCR_MODE');
    }

    protected function tearDown(): void
    {
        putenv('OAS_FAKE_VCR_MODE');
    }

    public function testDefaultModeIsReplay(): void
    {
        $config = new Configuration();

        self::assertSame(Mode::REPLAY, $config->getMode());
    }

    public function testDefaultCassettePath(): void
    {
        $config = new Configuration();

        self::assertSame('./cassettes', $config->getCassettePath());
    }

    public function testDefaultValidationEnabled(): void
    {
        $config = new Configuration();

        self::assertTrue($config->shouldValidateRequests());
        self::assertTrue($config->shouldValidateResponses());
    }

    public function testFromYamlFile(): void
    {
        $config = new Configuration();
        $result = $config->fromYamlFile($this->fixturesPath . '/petstore.yaml');

        self::assertSame($config, $result);
        self::assertTrue($config->hasSchema());
        self::assertInstanceOf(OpenApi::class, $config->getSchema());
    }

    public function testFromYamlFileThrowsOnNotFound(): void
    {
        $config = new Configuration();

        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage('not-found.yaml');

        $config->fromYamlFile('/path/to/not-found.yaml');
    }

    public function testFromJsonFile(): void
    {
        // Create a temporary JSON file
        $jsonPath = $this->fixturesPath . '/simple.json';
        $jsonContent = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($jsonPath, $jsonContent);

        try {
            $config = new Configuration();
            $result = $config->fromJsonFile($jsonPath);

            self::assertSame($config, $result);
            self::assertTrue($config->hasSchema());
        } finally {
            unlink($jsonPath);
        }
    }

    public function testFromJsonFileThrowsOnNotFound(): void
    {
        $config = new Configuration();

        $this->expectException(SchemaNotFoundException::class);

        $config->fromJsonFile('/path/to/not-found.json');
    }

    public function testFromJsonString(): void
    {
        $json = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Test API', 'version' => '1.0.0'],
            'paths' => [],
        ], JSON_THROW_ON_ERROR);

        $config = new Configuration();
        $result = $config->fromJsonString($json);

        self::assertSame($config, $result);
        self::assertTrue($config->hasSchema());
        self::assertSame('Test API', $config->getSchema()->info->title);
    }

    public function testFromYamlString(): void
    {
        $yaml = <<<'YAML'
            openapi: 3.0.0
            info:
              title: Test YAML API
              version: 1.0.0
            paths: {}
            YAML;

        $config = new Configuration();
        $result = $config->fromYamlString($yaml);

        self::assertSame($config, $result);
        self::assertTrue($config->hasSchema());
        self::assertSame('Test YAML API', $config->getSchema()->info->title);
    }

    public function testFromSchema(): void
    {
        $schema = new OpenApi([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [],
        ]);

        $config = new Configuration();
        $result = $config->fromSchema($schema);

        self::assertSame($config, $result);
        self::assertSame($schema, $config->getSchema());
    }

    public function testSetMode(): void
    {
        $config = new Configuration();
        $result = $config->setMode(Mode::RECORD);

        self::assertSame($config, $result);
        self::assertSame(Mode::RECORD, $config->getMode());
    }

    public function testSetCassettePath(): void
    {
        $config = new Configuration();
        $result = $config->setCassettePath('/custom/path');

        self::assertSame($config, $result);
        self::assertSame('/custom/path', $config->getCassettePath());
    }

    public function testEnableRequestValidation(): void
    {
        $config = new Configuration();

        $config->enableRequestValidation(false);
        self::assertFalse($config->shouldValidateRequests());

        $config->enableRequestValidation(true);
        self::assertTrue($config->shouldValidateRequests());
    }

    public function testEnableResponseValidation(): void
    {
        $config = new Configuration();

        $config->enableResponseValidation(false);
        self::assertFalse($config->shouldValidateResponses());

        $config->enableResponseValidation(true);
        self::assertTrue($config->shouldValidateResponses());
    }

    public function testSetFakerOptions(): void
    {
        $config = new Configuration();
        $options = ['alwaysFakeOptionals' => true, 'minItems' => 2];

        $result = $config->setFakerOptions($options);

        self::assertSame($config, $result);
        self::assertSame($options, $config->getFakerOptions());
    }

    public function testGetSchemaThrowsWhenNotLoaded(): void
    {
        $config = new Configuration();

        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage('No OpenAPI schema has been loaded. Call fromYamlFile(), fromJsonFile(), fromJsonString(), fromYamlString(), or fromSchema() first.');

        $config->getSchema();
    }

    public function testHasSchemaReturnsFalseWhenNotLoaded(): void
    {
        $config = new Configuration();

        self::assertFalse($config->hasSchema());
    }

    public function testFluentInterface(): void
    {
        $config = new Configuration();

        $result = $config
            ->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::RECORD)
            ->setCassettePath('/tmp/cassettes')
            ->enableRequestValidation(false)
            ->enableResponseValidation(false)
            ->setFakerOptions(['minItems' => 1]);

        self::assertSame($config, $result);
    }
}
