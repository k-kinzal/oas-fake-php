<?php

declare(strict_types=1);

namespace OasFake;

use function preg_match;
use function preg_quote;
use function preg_replace;

/**
 * Matches concrete request paths against OpenAPI path templates.
 *
 * @visibility namespace
 */
final class RequestPathMatcher
{
    /**
     * Report whether the request path satisfies the template segments.
     */
    public function matches(string $pattern, string $path): bool
    {
        $quoted = preg_quote($pattern, '#');
        $regex = preg_replace('#\\\\\{[^}/]+\\\\\}#', '[^/]+', $quoted);
        if ($regex === null) {
            return false;
        }

        return preg_match('#^' . $regex . '$#', $path) === 1;
    }
}
