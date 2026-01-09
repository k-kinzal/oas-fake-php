<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFakePHP\Config\Configuration;
use OasFakePHP\OasFake;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Vcr\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(OasFake::class)]
final class OasFakeTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        OasFake::resetInstance();
        $this->fixturesPath = __DIR__ . '/../Fixtures/openapi';
    }

    protected function tearDown(): void
    {
        OasFake::resetInstance();
    }

    public function testGetInstance(): void
    {
        $instance1 = OasFake::getInstance();
        $instance2 = OasFake::getInstance();

        self::assertSame($instance1, $instance2);
    }

    public function testFromYamlFile(): void
    {
        $config = OasFake::fromYamlFile($this->fixturesPath . '/petstore.yaml');

        self::assertInstanceOf(Configuration::class, $config);
        self::assertTrue(OasFake::getConfiguration()->hasSchema());
    }

    public function testFromJsonString(): void
    {
        $json = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [],
        ], JSON_THROW_ON_ERROR);

        $config = OasFake::fromJsonString($json);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertTrue(OasFake::getConfiguration()->hasSchema());
    }

    public function testFromYamlString(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";

        $config = OasFake::fromYamlString($yaml);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertTrue(OasFake::getConfiguration()->hasSchema());
    }

    public function testRegisterCallback(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        OasFake::registerCallback('testOp', $callback);

        $registry = OasFake::getCallbackRegistry();
        self::assertTrue($registry->hasForOperationId('testOp'));
    }

    public function testRegisterCallbackForPath(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        OasFake::registerCallbackForPath('/pets', 'GET', $callback);

        $registry = OasFake::getCallbackRegistry();
        self::assertInstanceOf(CallbackRegistry::class, $registry);
    }

    public function testStartAndStop(): void
    {
        OasFake::fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath(sys_get_temp_dir());

        self::assertFalse(OasFake::isRunning());

        OasFake::start();
        self::assertTrue(OasFake::isRunning());

        OasFake::stop();
        self::assertFalse(OasFake::isRunning());
    }

    public function testReset(): void
    {
        OasFake::fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath(sys_get_temp_dir());

        OasFake::registerCallback('test', static fn ($req, $res) => new Response());

        OasFake::start();
        self::assertTrue(OasFake::isRunning());

        OasFake::reset();
        self::assertFalse(OasFake::isRunning());
        self::assertFalse(OasFake::getCallbackRegistry()->hasForOperationId('test'));
    }

    public function testGetConfiguration(): void
    {
        $config = OasFake::getConfiguration();

        self::assertInstanceOf(Configuration::class, $config);
    }

    public function testGetCallbackRegistry(): void
    {
        $registry = OasFake::getCallbackRegistry();

        self::assertInstanceOf(CallbackRegistry::class, $registry);
    }

    public function testResetInstance(): void
    {
        $instance1 = OasFake::getInstance();

        OasFake::resetInstance();

        $instance2 = OasFake::getInstance();

        self::assertNotSame($instance1, $instance2);
    }

    public function testStopWhenNotStarted(): void
    {
        // Should not throw
        OasFake::stop();
        self::assertFalse(OasFake::isRunning());
    }

    public function testStartWithoutSchemaThrowsException(): void
    {
        $this->expectException(\OasFakePHP\Exception\SchemaNotFoundException::class);
        $this->expectExceptionMessage('No OpenAPI schema has been loaded');

        OasFake::start();
    }
}
