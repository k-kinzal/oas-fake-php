<?php

declare(strict_types=1);

namespace OasFake;

use Closure;
use VCR\LibraryHooks\LibraryHook;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;
use VCR\VCRFactory;

/**
 * Owns the process-wide PHP-VCR activation and dispatch hook lifecycle.
 */
final class VcrLifecycle
{
    private bool $active = false;

    /**
     * Activate PHP-VCR once and bind intercepted requests to a dispatcher.
     *
     * @param callable(VcrRequest): VcrResponse $dispatch
     */
    public function activate(callable $dispatch): void
    {
        if ($this->active) {
            return;
        }

        VCR::configure()
            ->setCassettePath(sys_get_temp_dir())
            ->setStorage('json')
            ->setMode('none')
            ->enableLibraryHooks(['curl', 'stream_wrapper']);
        VCR::turnOn();
        VCR::insertCassette('oas-fake-registry');

        $handler = Closure::fromCallable($dispatch);
        foreach (VCR::configure()->getLibraryHooks() as $hookClass) {
            /** @var LibraryHook $hook */
            $hook = VCRFactory::get($hookClass);
            $hook->disable();
            $hook->enable($handler);
        }

        $this->active = true;
    }

    /**
     * Deactivate PHP-VCR when the final server stops.
     */
    public function deactivate(): void
    {
        if (!$this->active) {
            return;
        }

        VCR::turnOff();
        $this->active = false;
    }

    /**
     * Report whether this lifecycle currently owns an active PHP-VCR session.
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
