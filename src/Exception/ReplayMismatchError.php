<?php

declare(strict_types=1);

namespace OasFake\Exception;

use AssertionError;
use LogicException;
use VCR\Request as VcrRequest;

/**
 * Thrown when a request in REPLAY mode does not match any cassette recording.
 *
 * Extends \AssertionError so PHPUnit treats it as a test failure.
 */
final class ReplayMismatchError extends AssertionError
{
    public static function forRequest(VcrRequest $request, LogicException $previous): self
    {
        $method = $request->getMethod();
        $url = $request->getUrl() ?? '(unknown)';
        $body = $request->getBody();

        $lines = [
            sprintf('REPLAY: no matching cassette recording for %s %s', $method, $url),
        ];

        if ($body !== null && $body !== '') {
            $truncated = mb_strlen($body) > 200
                ? mb_substr($body, 0, 200) . '...'
                : $body;
            $lines[] = sprintf('Request body: %s', $truncated);
        }

        $lines[] = 'Matching: method, URL path, host, query string, body, post fields, headers.';
        $lines[] = 'Re-run in RECORD mode to update the cassette.';

        return new self(implode("\n", $lines), 0, $previous);
    }
}
