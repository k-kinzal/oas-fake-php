<?php

declare(strict_types=1);

namespace OasFakePHP\Server;

trait ValidationSetting
{
    protected static ?bool $VALIDATE_REQUESTS = null;

    protected static ?bool $VALIDATE_RESPONSES = null;

    private ?bool $validateRequests = null;

    private ?bool $validateResponses = null;

    public function withRequestValidation(bool $enable = true): static
    {
        $this->validateRequests = $enable;

        return $this;
    }

    public function withResponseValidation(bool $enable = true): static
    {
        $this->validateResponses = $enable;

        return $this;
    }

    protected function shouldValidateRequests(): bool
    {
        if ($this->validateRequests !== null) {
            return $this->validateRequests;
        }

        if (static::$VALIDATE_REQUESTS !== null) {
            return static::$VALIDATE_REQUESTS;
        }

        return true;
    }

    protected function shouldValidateResponses(): bool
    {
        if ($this->validateResponses !== null) {
            return $this->validateResponses;
        }

        if (static::$VALIDATE_RESPONSES !== null) {
            return static::$VALIDATE_RESPONSES;
        }

        return true;
    }
}
