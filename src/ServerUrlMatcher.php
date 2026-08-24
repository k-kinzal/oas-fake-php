<?php

declare(strict_types=1);

namespace OasFake;

use function is_array;
use function ltrim;
use function parse_url;

use Psr\Http\Message\ServerRequestInterface;

use function rtrim;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * Matches request URLs and paths against normalized OpenAPI server URLs.
 *
 * @visibility namespace
 */
final class ServerUrlMatcher
{
    /**
     * Check scheme, host, and effective port against a parsed server URL.
     *
     * @param array{scheme?: string, host?: string, port?: int|string, path?: string} $base
     */
    public function matchesRequest(ServerRequestInterface $request, array $base): bool
    {
        $uri = $request->getUri();

        if (isset($base['scheme']) && strtolower($uri->getScheme()) !== strtolower((string) $base['scheme'])) {
            return false;
        }

        if (isset($base['host']) && strtolower($uri->getHost()) !== strtolower((string) $base['host'])) {
            return false;
        }

        $basePort = $this->effectivePort($base);

        return $basePort === null || $this->effectiveRequestPort($request) === $basePort;
    }

    /**
     * Return the path-specificity score when a URL matches a server URL.
     */
    public function specificity(string $requestUrl, string $baseUrl): ?int
    {
        if ($baseUrl === '/') {
            return 0;
        }

        $request = parse_url($requestUrl);
        $base = parse_url($baseUrl);
        if (!is_array($request) || !is_array($base)) {
            return null;
        }

        if (isset($base['scheme']) && strtolower((string) ($request['scheme'] ?? '')) !== strtolower((string) $base['scheme'])) {
            return null;
        }

        if (isset($base['host']) && strtolower((string) ($request['host'] ?? '')) !== strtolower((string) $base['host'])) {
            return null;
        }

        $basePort = $this->effectivePort($base);
        if ($basePort !== null && $this->effectivePort($request) !== $basePort) {
            return null;
        }

        return $this->pathPrefixSpecificity((string) ($request['path'] ?? '/'), (string) ($base['path'] ?? '/'));
    }

    /**
     * Return an explicit or scheme-default port for a parsed URL.
     *
     * @param array{scheme?: string, port?: int|string} $url
     */
    public function effectivePort(array $url): ?int
    {
        if (isset($url['port'])) {
            return (int) $url['port'];
        }

        return match (strtolower((string) ($url['scheme'] ?? ''))) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    /**
     * Return an explicit or scheme-default port for a PSR-7 request.
     */
    public function effectiveRequestPort(ServerRequestInterface $request): ?int
    {
        $uri = $request->getUri();
        if ($uri->getPort() !== null) {
            return $uri->getPort();
        }

        return match (strtolower($uri->getScheme())) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    /**
     * Return the matched prefix length while respecting segment boundaries.
     */
    public function pathPrefixSpecificity(string $requestPath, string $basePath): ?int
    {
        $normalizedRequest = $this->normalizePath($requestPath);
        $normalizedBase = $this->normalizePath($basePath);

        if ($normalizedBase === '/') {
            return 0;
        }

        if ($normalizedRequest !== $normalizedBase && !str_starts_with($normalizedRequest, $normalizedBase . '/')) {
            return null;
        }

        return strlen($normalizedBase);
    }

    /**
     * Strip a matched server base path from a request path.
     */
    public function stripBasePath(string $path, string $basePath): ?string
    {
        if ($basePath === '/') {
            return $path;
        }

        if ($path === $basePath) {
            return '/';
        }

        if (!str_starts_with($path, $basePath . '/')) {
            return null;
        }

        $operationPath = substr($path, strlen($basePath));

        return $operationPath === '' ? '/' : $operationPath;
    }

    /**
     * Normalize a URL path to one leading slash and no trailing slash.
     */
    public function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        $normalized = rtrim($normalized, '/');

        return $normalized === '' ? '/' : $normalized;
    }
}
