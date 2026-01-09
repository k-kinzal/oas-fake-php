<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

trait CassetteSetting
{
    protected static ?string $CASSETTE_PATH = null;

    private ?string $cassettePath = null;

    public function withCassettePath(string $path): static
    {
        $this->cassettePath = $path;

        return $this;
    }

    protected function cassettePath(): string
    {
        if ($this->cassettePath !== null) {
            return $this->cassettePath;
        }

        if (static::$CASSETTE_PATH !== null) {
            return static::$CASSETTE_PATH;
        }

        return './cassettes';
    }
}
