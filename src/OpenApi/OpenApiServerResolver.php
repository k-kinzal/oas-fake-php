<?php

declare(strict_types=1);

namespace OasFake;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Server as CebeServer;

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

        /** @var PathItem $pathItem */
        foreach ($openApi->paths as $_path => $pathItem) {
            foreach ($pathItem->getOperations() as $operation) {
                foreach ($this->effective($openApi, $pathItem, $operation) as $url) {
                    $urls[$url] = true;
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
        if ($operation !== null && $operation->servers !== []) {
            return $this->substituteVariables($operation->servers);
        }

        if ($pathItem !== null && $pathItem->servers !== []) {
            return $this->substituteVariables($pathItem->servers);
        }

        return $this->substituteVariables($openApi->servers);
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
            $url = $server->url;
            foreach ($server->variables as $name => $variable) {
                $url = str_replace('{' . $name . '}', $variable->default, $url);
            }
            $urls[] = $url;
        }

        return $urls === [] ? ['/'] : $urls;
    }
}
