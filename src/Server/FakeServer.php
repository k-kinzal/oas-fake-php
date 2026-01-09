<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Vcr\VcrManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionMethod;

class FakeServer implements Server
{
    use SchemaSetting;
    use ModeSetting;
    use ValidationSetting;
    use CassetteSetting;
    use FakerSetting;

    private ?VcrManager $vcrManager = null;
    private CallbackRegistry $callbackRegistry;
    private ?Configuration $configuration = null;

    public function __construct()
    {
        $this->callbackRegistry = new CallbackRegistry();
        $this->registerCallbacksFromMethods();
    }

    public function start(): void
    {
        $configuration = $this->getConfiguration();

        if ($this->vcrManager === null) {
            $this->vcrManager = new VcrManager(
                $configuration,
                $this->callbackRegistry,
            );
        }

        $this->vcrManager->start();
    }

    public function stop(): void
    {
        if ($this->vcrManager !== null) {
            $this->vcrManager->stop();
        }
    }

    public function isRunning(): bool
    {
        return $this->vcrManager !== null && $this->vcrManager->isRunning();
    }

    public function getConfiguration(): Configuration
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }

        $this->configuration = new Configuration();

        $schema = $this->resolveSchema();
        $this->configuration->fromSchema($schema);

        $this->configuration
            ->setMode($this->mode())
            ->setCassettePath($this->cassettePath())
            ->enableRequestValidation($this->shouldValidateRequests())
            ->enableResponseValidation($this->shouldValidateResponses())
            ->setFakerOptions($this->fakerOptions());

        return $this->configuration;
    }

    public function getCallbackRegistry(): CallbackRegistry
    {
        return $this->callbackRegistry;
    }

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public function registerCallback(string $operationId, callable $callback): static
    {
        $this->callbackRegistry->register($operationId, $callback);

        return $this;
    }

    /**
     * @param callable(ServerRequestInterface, ResponseInterface|null): ResponseInterface $callback
     */
    public function registerCallbackForPath(string $path, string $method, callable $callback): static
    {
        $this->callbackRegistry->registerForPath($path, $method, $callback);

        return $this;
    }

    private function registerCallbacksFromMethods(): void
    {
        $reflection = new ReflectionClass($this);
        $baseMethods = $this->getBaseMethods();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            if (in_array($method->getName(), $baseMethods, true)) {
                continue;
            }

            $callbackAttribute = $this->getCallbackAttribute($method);

            if ($callbackAttribute !== null) {
                $this->callbackRegistry->registerForPath(
                    $callbackAttribute->path,
                    $callbackAttribute->method,
                    $method->getClosure($this),
                );
            } else {
                $this->callbackRegistry->register(
                    $method->getName(),
                    $method->getClosure($this),
                );
            }
        }
    }

    private function getCallbackAttribute(ReflectionMethod $method): ?Callback
    {
        $attributes = $method->getAttributes(Callback::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * @return list<string>
     */
    private function getBaseMethods(): array
    {
        return [
            'start',
            'stop',
            'isRunning',
            'getConfiguration',
            'getCallbackRegistry',
            'registerCallback',
            'registerCallbackForPath',
            'withSchemaFile',
            'withSchemaString',
            'withSchema',
            'withMode',
            'withRequestValidation',
            'withResponseValidation',
            'withCassettePath',
            'withFakerOptions',
        ];
    }
}
