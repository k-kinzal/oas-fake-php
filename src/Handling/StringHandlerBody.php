<?php

declare(strict_types=1);

namespace OasFake;

/**
 * Returns an already encoded handler response body.
 *
 * @visibility namespace
 */
final class StringHandlerBody implements HandlerBody
{
    /**
     * Store an already encoded response body.
     *
     * @param string $value The encoded response body
     */
    public function __construct(private string $value)
    {
    }

    /**
     * Return the encoded response body unchanged.
     */
    public function encode(): string
    {
        return $this->value;
    }
}
