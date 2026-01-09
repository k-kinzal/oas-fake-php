<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

trait FakerSetting
{
    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}|null */
    protected static ?array $FAKER_OPTIONS = null;

    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}|null */
    private ?array $fakerOptions = null;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function withFakerOptions(array $options): static
    {
        $this->fakerOptions = $options;

        return $this;
    }

    /**
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    protected function fakerOptions(): array
    {
        if ($this->fakerOptions !== null) {
            return $this->fakerOptions;
        }

        if (static::$FAKER_OPTIONS !== null) {
            return static::$FAKER_OPTIONS;
        }

        return [];
    }
}
