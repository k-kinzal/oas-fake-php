<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Server as CebeServer;

use function is_string;

/**
 * Resolves the effective server URLs declared at OpenAPI hierarchy levels.
 *
 * @visibility namespace
 */
final class OpenApiServerResolver
{
    /**
     * Return every effective server URL used by at least one operation.
     *
     * @return list<string>
     */
    public function all(OpenApi $openApi): array
    {
        $urls = [];

        if ($openApi->paths !== null) {
            /** @var PathItem $pathItem */
            foreach ($openApi->paths as $path => $pathItem) {
                if (!is_string($path)) {
                    continue;
                }

                foreach ($pathItem->getOperations() as $operation) {
                    foreach ($this->effective($openApi, $pathItem, $operation) as $url) {
                        $urls[$url] = true;
                    }
                }
            }
        }

        return $urls === [] ? $this->effective($openApi) : array_keys($urls);
    }

    /**
     * Return server URLs using operation, path, and document precedence.
     *
     * @return list<string>
     */
    public function effective(OpenApi $openApi, ?PathItem $pathItem = null, ?Operation $operation = null): array
    {
        if ($operation !== null && $operation->servers !== null && $operation->servers !== []) {
            return $this->substituteVariables($operation->servers);
        }

        if ($pathItem !== null && $pathItem->servers !== null && $pathItem->servers !== []) {
            return $this->substituteVariables($pathItem->servers);
        }

        if ($openApi->servers !== null && $openApi->servers !== []) {
            return $this->substituteVariables($openApi->servers);
        }

        return ['/'];
    }

    /**
     * Substitute defaults for variables in a list of OpenAPI servers.
     *
     * @param array<int|string, CebeServer> $servers
     *
     * @return list<string>
     */
    public function substituteVariables(array $servers): array
    {
        $urls = [];

        foreach ($servers as $server) {
            if (!$server instanceof CebeServer) {
                continue;
            }

            $url = $server->url;
            if ($server->variables !== null) {
                foreach ($server->variables as $name => $variable) {
                    $url = str_replace('{' . $name . '}', $variable->default, $url);
                }
            }
            $urls[] = $url;
        }

        return $urls === [] ? ['/'] : $urls;
    }
}
