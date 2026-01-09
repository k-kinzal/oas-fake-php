<?php

declare(strict_types=1);

namespace OasFakePHP\Vcr;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;
use VCR\VCR;

final class VcrManager
{
    private bool $isRunning = false;
    private ?FakeRequestHandler $requestHandler = null;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CallbackRegistry $callbackRegistry,
    ) {
    }

    public function start(): void
    {
        if ($this->isRunning) {
            return;
        }

        $this->configureVcr();

        VCR::turnOn();
        $this->postStartSetup();
        $this->isRunning = true;
    }

    public function stop(): void
    {
        if (!$this->isRunning) {
            return;
        }

        VCR::turnOff();
        $this->isRunning = false;
        $this->requestHandler = null;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function getRequestHandler(): FakeRequestHandler
    {
        if ($this->requestHandler === null) {
            $this->requestHandler = new FakeRequestHandler(
                $this->configuration,
                $this->callbackRegistry,
            );
        }

        return $this->requestHandler;
    }

    private function configureVcr(): void
    {
        $mode = $this->configuration->getMode();

        VCR::configure()
            ->setCassettePath($this->configuration->getCassettePath())
            ->setStorage('json')
            ->enableLibraryHooks(['curl', 'stream_wrapper']);

        match ($mode) {
            Mode::RECORD => $this->configureRecordMode(),
            Mode::REPLAY => $this->configureReplayMode(),
            Mode::PASSTHROUGH => $this->configurePassthroughMode(),
        };
    }

    private function configureRecordMode(): void
    {
        VCR::configure()->setMode('new_episodes');
    }

    private function configureReplayMode(): void
    {
        // In replay mode, we use a custom request matcher that intercepts all requests
        VCR::configure()
            ->setMode('none')
            ->enableRequestMatchers(['method', 'url']);
    }

    private function configurePassthroughMode(): void
    {
        VCR::configure()->setMode('none');
    }

    private function postStartSetup(): void
    {
        $mode = $this->configuration->getMode();

        match ($mode) {
            Mode::RECORD => VCR::insertCassette('recording'),
            Mode::REPLAY => $this->setupReplayMode(),
            Mode::PASSTHROUGH => null,
        };
    }

    private function setupReplayMode(): void
    {
        $handler = $this->getRequestHandler();

        // Using PHP-VCR's hook system to intercept requests
        VCR::configure()->addRequestMatcher(
            'fake_response',
            static function (): bool {
                return true; // Match everything
            },
        );

        // Insert a cassette
        VCR::insertCassette('fake');
    }
}
