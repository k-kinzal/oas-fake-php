<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

use OasFakePHP\Vcr\Mode;

trait ModeSetting
{
    protected static ?Mode $MODE = null;

    private ?Mode $mode = null;

    public function withMode(Mode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    protected function mode(): Mode
    {
        if ($this->mode !== null) {
            return $this->mode;
        }

        if (static::$MODE !== null) {
            return static::$MODE;
        }

        return Mode::fromEnvironment();
    }
}
