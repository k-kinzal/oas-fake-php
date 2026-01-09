<?php

declare(strict_types=1);

namespace OasFakePHP;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Server\Server;
use OasFakePHP\Vcr\VcrManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OasFake
{
    private static ?self $instance = null;
    private Configuration $configuration;
    private CallbackRegistry $callbackRegistry;
    private ?VcrManager $vcrManager = null;
    private ?Server $currentServer = null;

    private function __construct()
    {
        $this->configuration = new Configuration();
        $this->callbackRegistry = new CallbackRegistry();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function fromYamlFile(string $path): Configuration
    {
        return self::getInstance()->configuration->fromYamlFile($path);
    }

    public static function fromJsonFile(string $path): Configuration
    {
        return self::getInstance()->configuration->fromJsonFile($path);
    }

    public static function fromJsonString(string $json): Configuration
    {
        return self::getInstance()->configuration->fromJsonString($json);
    }

    public static function fromYamlString(string $yaml): Configuration
    {
        return self::getInstance()->configuration->fromYamlString($yaml);
    }

    /**
     * @deprecated Use fromYamlFile(), fromJsonFile(), fromJsonString(), or fromYamlString() instead
     */
    public static function configure(): Configuration
    {
        return self::getInstance()->configuration;
    }

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public static function registerCallback(string $operationId, callable $callback): void
    {
        self::getInstance()->callbackRegistry->register($operationId, $callback);
    }

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public static function registerCallbackForPath(string $path, string $method, callable $callback): void
    {
        self::getInstance()->callbackRegistry->registerForPath($path, $method, $callback);
    }

    /**
     * @param class-string<Server>|Server|null $server
     */
    public static function start(Server|string|null $server = null): void
    {
        $instance = self::getInstance();

        if ($server !== null) {
            if (is_string($server)) {
                $server = new $server();
            }

            $instance->currentServer = $server;
            $server->start();

            return;
        }

        // Legacy behavior: use singleton configuration
        $instance->configuration->getSchema();

        if ($instance->vcrManager === null) {
            $instance->vcrManager = new VcrManager(
                $instance->configuration,
                $instance->callbackRegistry,
            );
        }

        $instance->vcrManager->start();
    }

    public static function stop(): void
    {
        $instance = self::getInstance();

        if ($instance->currentServer !== null) {
            $instance->currentServer->stop();
            $instance->currentServer = null;

            return;
        }

        if ($instance->vcrManager !== null) {
            $instance->vcrManager->stop();
        }
    }

    public static function reset(): void
    {
        $instance = self::getInstance();

        if ($instance->currentServer !== null) {
            $instance->currentServer->stop();
            $instance->currentServer = null;
        }

        if ($instance->vcrManager !== null) {
            $instance->vcrManager->stop();
            $instance->vcrManager = null;
        }

        $instance->callbackRegistry->clear();
        $instance->configuration = new Configuration();
    }

    public static function isRunning(): bool
    {
        $instance = self::getInstance();

        if ($instance->currentServer !== null) {
            return $instance->currentServer->isRunning();
        }

        return $instance->vcrManager !== null && $instance->vcrManager->isRunning();
    }

    public static function getConfiguration(): Configuration
    {
        return self::getInstance()->configuration;
    }

    public static function getCallbackRegistry(): CallbackRegistry
    {
        return self::getInstance()->callbackRegistry;
    }

    /**
     * Reset the singleton instance (useful for testing)
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->stopInstance();
            self::$instance = null;
        }
    }

    public static function getServer(): ?Server
    {
        return self::getInstance()->currentServer;
    }

    private function stopInstance(): void
    {
        if ($this->currentServer !== null) {
            $this->currentServer->stop();
        }

        if ($this->vcrManager !== null) {
            $this->vcrManager->stop();
        }
    }
}
