<?php

declare(strict_types=1);

namespace OasFake;

use function is_array;
use function parse_url;

use Psr\Http\Message\ServerRequestInterface;

use function strlen;

/**
 * Resolves request paths relative to OpenAPI server base URLs.
 *
 * @visibility namespace
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
        $urlMatcher = new ServerUrlMatcher();
        $path = $urlMatcher->normalizePath($request->getUri()->getPath());
        /** @var array{specificity: int, path: string, serverUrl: string}|null $match */
        $match = null;

        foreach ($schema->serverUrls() as $serverUrl) {
            $base = parse_url($serverUrl);
            if (!is_array($base) || !$urlMatcher->matchesRequest($request, $base)) {
                continue;
            }

            $basePath = $urlMatcher->normalizePath((string) ($base['path'] ?? '/'));
            $operationPath = $urlMatcher->stripBasePath($path, $basePath);
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
}
