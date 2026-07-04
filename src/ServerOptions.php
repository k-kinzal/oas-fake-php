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
     * Normalized server mode.
     */
    public Mode $mode;

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $fakerOptions
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        public Schema $schema,
        string|Mode $mode,
        public string $cassettePath,
        public bool $validateRequests,
        public bool $validateResponses,
        public array $fakerOptions,
        public array $middleware,
    ) {
        $this->mode = Mode::from($mode);
    }
}
