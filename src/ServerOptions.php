<?php

declare(strict_types=1);

namespace OasFake;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Resolved configuration used to construct a server interceptor.
 */
final class ServerOptions
{
    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        public Schema $schema,
        public string $mode,
        public string $cassettePath,
        public bool $validateRequests,
        public bool $validateResponses,
        public array $fakerOptions,
        public array $middleware,
    ) {
    }
}
