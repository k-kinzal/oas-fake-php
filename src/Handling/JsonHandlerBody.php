<?php

declare(strict_types=1);

namespace OasFake;

use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;

/**
 * Defers JSON encoding while preserving the accepted array type.
 *
 * @template TBody of array
 *
 * @visibility namespace
 */
final class JsonHandlerBody implements HandlerBody
{
    /**
     * @param TBody $value
     */
    public function __construct(private array $value)
    {
    }

    /**
     * @throws JsonException when the body cannot be encoded
     */
    public function encode(): string
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR);
    }
}
