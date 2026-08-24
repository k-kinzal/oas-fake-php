<?php

declare(strict_types=1);

namespace OasFake\Exception;

use LogicException;
use VCR\Request as VcrRequest;

/**
 * Thrown when a request in REPLAY mode does not match any cassette recording.
 *
 * The historical Error suffix is retained for backward compatibility; replay
 * mismatches are recoverable runtime failures that callers may handle.
 */
final class ReplayMismatchError extends OasFakeException
{
    /**
     * Create an assertion-style error for an unmatched replay request.
     *
     * @param VcrRequest $request The request that could not be matched
     * @param LogicException $previous The original cassette lookup failure
     */
    public static function forRequest(VcrRequest $request, LogicException $previous): self
    {
        $method = $request->getMethod();
        $url = $request->getUrl() ?? '(unknown)';
        $body = $request->getBody();

        $lines = [
            sprintf('REPLAY: no matching cassette recording for %s %s', $method, $url),
        ];

        if ($body !== null && $body !== '') {
            $truncated = strlen($body) > 200
                ? substr($body, 0, 200) . '...'
                : $body;
            $lines[] = sprintf('Request body: %s', $truncated);
        }

        $lines[] = 'Matching: method, URL path, host, query string, body, post fields, headers.';
        $lines[] = 'Re-run in RECORD mode to update the cassette.';

        return new self(implode("\n", $lines), 0, $previous);
    }
}
