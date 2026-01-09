<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Vcr;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Vcr\FakeRequestHandler;
use OasFakePHP\Vcr\Mode;
use OasFakePHP\Vcr\VcrManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VcrManager::class)]
final class VcrManagerTest extends TestCase
{
    private string $fixturesPath;
    private string $cassettePath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures/openapi';
        $this->cassettePath = sys_get_temp_dir() . '/oas-fake-test-cassettes';
        if (!is_dir($this->cassettePath)) {
            mkdir($this->cassettePath, 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up cassette directory
        if (is_dir($this->cassettePath)) {
            array_map('unlink', glob($this->cassettePath . '/*') ?: []);
            rmdir($this->cassettePath);
        }
    }

    public function testStartAndStop(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        self::assertFalse($manager->isRunning());

        $manager->start();
        self::assertTrue($manager->isRunning());

        $manager->stop();
        self::assertFalse($manager->isRunning());
    }

    public function testStartIsIdempotent(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        $manager->start();
        $manager->start(); // Should not throw

        self::assertTrue($manager->isRunning());

        $manager->stop();
    }

    public function testStopIsIdempotent(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        $manager->stop(); // Should not throw when not running
        $manager->stop();

        self::assertFalse($manager->isRunning());
    }

    public function testGetRequestHandler(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::PASSTHROUGH)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        $handler = $manager->getRequestHandler();

        self::assertInstanceOf(FakeRequestHandler::class, $handler);

        // Same instance returned
        self::assertSame($handler, $manager->getRequestHandler());
    }

    public function testRecordMode(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::RECORD)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        $manager->start();
        self::assertTrue($manager->isRunning());

        $manager->stop();
    }

    public function testReplayMode(): void
    {
        $config = new Configuration();
        $config->fromYamlFile($this->fixturesPath . '/petstore.yaml')
            ->setMode(Mode::REPLAY)
            ->setCassettePath($this->cassettePath);

        $manager = new VcrManager($config, new CallbackRegistry());

        $manager->start();
        self::assertTrue($manager->isRunning());

        $manager->stop();
    }
}
