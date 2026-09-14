<?php

declare(strict_types=1);

namespace OasFake;

use JsonException;

/**
 * Encodes a handler response body when the handler is resolved.
 *
 * @visibility namespace
 */
interface HandlerBody
{
    /**
     * @throws JsonException when the body cannot be encoded
     */
    public function encode(): string;
}
