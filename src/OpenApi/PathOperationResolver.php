<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

/**
 * Resolves a typed operation property from an OpenAPI path item.
 *
 * @visibility namespace
 */
final class PathOperationResolver
{
    /**
     * Return the operation declared for a supported lowercase HTTP method.
     */
    public function resolve(PathItem $pathItem, string $method): ?Operation
    {
        return match ($method) {
            'get' => $pathItem->get,
            'post' => $pathItem->post,
            'put' => $pathItem->put,
            'delete' => $pathItem->delete,
            'patch' => $pathItem->patch,
            'options' => $pathItem->options,
            'head' => $pathItem->head,
            'trace' => $pathItem->trace,
            default => null,
        };
    }
}
