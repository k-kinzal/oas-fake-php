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
 * Resolves request paths relative to OpenAPI server base URLs.
 */
final class OperationPathResolver
{
    /**
     * Return the path used by OpenAPI operation lookup for a request.
     */
    public function resolve(Schema $schema, ServerRequestInterface $request): string
    {
        return $this->resolveWithServerUrl($schema, $request)['path'];
    }

    /**
     * Return the operation path and the server URL that was used to resolve it.
     *
     * @return array{path: string, serverUrl: string|null}
     */
    public function resolveWithServerUrl(Schema $schema, ServerRequestInterface $request): array
    {
        $path = $this->normalizePath($request->getUri()->getPath());
        /** @var array{specificity: int, path: string, serverUrl: string}|null $match */
        $match = null;

        foreach ($schema->serverUrls() as $serverUrl) {
            $base = parse_url($serverUrl);
            if (!is_array($base) || !$this->serverUrlMatchesRequest($request, $base)) {
                continue;
            }

            $basePath = $this->normalizePath((string) ($base['path'] ?? '/'));
            $operationPath = $this->stripBasePath($path, $basePath);
            if ($operationPath === null) {
                continue;
            }

            $specificity = $basePath === '/' ? 0 : strlen($basePath);
            if ($match === null || $specificity > $match['specificity']) {
                $match = [
                    'specificity' => $specificity,
                    'path' => $operationPath,
                    'serverUrl' => $serverUrl,
                ];
            }
        }

        if ($match === null) {
            return [
                'path' => $path,
                'serverUrl' => null,
            ];
        }

        return [
            'path' => $match['path'],
            'serverUrl' => $match['serverUrl'],
        ];
    }

    /**
     * @param array{scheme?: string, host?: string, port?: int|string, path?: string} $base
     */
    private function serverUrlMatchesRequest(ServerRequestInterface $request, array $base): bool
    {
        $uri = $request->getUri();

        if (isset($base['scheme']) && strtolower($uri->getScheme()) !== strtolower((string) $base['scheme'])) {
            return false;
        }

        if (isset($base['host']) && strtolower($uri->getHost()) !== strtolower((string) $base['host'])) {
            return false;
        }

        $basePort = $this->effectivePort($base);
        if ($basePort !== null && $this->effectiveRequestPort($request) !== $basePort) {
            return false;
        }

        return true;
    }

    /**
     * @param array{scheme?: string, port?: int|string} $url
     */
    private function effectivePort(array $url): ?int
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

    private function effectiveRequestPort(ServerRequestInterface $request): ?int
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

    private function stripBasePath(string $path, string $basePath): ?string
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

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        $normalized = rtrim($normalized, '/');

        return $normalized === '' ? '/' : $normalized;
    }
}
