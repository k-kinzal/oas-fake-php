<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;

interface Server
{
    public function start(): void;

    public function stop(): void;

    public function isRunning(): bool;

    public function getConfiguration(): Configuration;

    public function getCallbackRegistry(): CallbackRegistry;
}
