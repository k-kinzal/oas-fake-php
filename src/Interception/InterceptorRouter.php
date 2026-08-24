<?php

declare(strict_types=1);

namespace OasFake;

use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

/**
 * Routes intercepted requests to the most specific registered server URL.
 *
 * @visibility namespace
 */
final class InterceptorRouter
{
    /**
     * @var array<string, list<array{key: string, interceptor: Interceptor, mode: Mode}>>
     */
    private array $interceptors = [];

    /**
     * @var array<string, list<string>>
     */
    private array $urlsByKey = [];

    /**
     * Create a router with URL matching semantics shared by operation lookup.
     */
    public function __construct(private ServerUrlMatcher $urlMatcher)
    {
    }

    /**
     * Add an interceptor under every effective server URL.
     *
     * @param list<string> $urls
     */
    public function add(string $key, array $urls, Interceptor $interceptor, Mode $mode): void
    {
        $this->urlsByKey[$key] = $urls;

        foreach ($urls as $url) {
            $this->interceptors[$url] ??= [];
            $this->interceptors[$url][] = [
                'key' => $key,
                'interceptor' => $interceptor,
                'mode' => $mode,
            ];
        }
    }

    /**
     * Remove every route owned by a server key.
     */
    public function remove(string $key): void
    {
        foreach ($this->urlsByKey[$key] ?? [] as $url) {
            $remaining = [];
            foreach ($this->interceptors[$url] ?? [] as $entry) {
                if ($entry['key'] !== $key) {
                    $remaining[] = $entry;
                }
            }

            if ($remaining === []) {
                unset($this->interceptors[$url]);
            } else {
                $this->interceptors[$url] = $remaining;
            }
        }

        unset($this->urlsByKey[$key]);
    }

    /**
     * Remove all registered routes.
     */
    public function clear(): void
    {
        $this->interceptors = [];
        $this->urlsByKey = [];
    }

    /**
     * Dispatch a request, or return null when no registered URL matches.
     */
    public function dispatch(VcrRequest $request): ?VcrResponse
    {
        $url = $request->getUrl() ?? '';
        $match = null;

        foreach ($this->interceptors as $baseUrl => $entries) {
            $specificity = $this->urlMatcher->specificity($url, $baseUrl);
            if ($specificity === null) {
                continue;
            }

            $entry = $entries[count($entries) - 1] ?? null;
            if ($entry !== null && ($match === null || $specificity > $match['specificity'])) {
                $match = [
                    'specificity' => $specificity,
                    'entry' => $entry,
                ];
            }
        }

        if ($match === null) {
            return null;
        }

        $entry = $match['entry'];

        return $entry['mode']->isReplay()
            ? $entry['interceptor']->replay($request)
            : $entry['interceptor']->handle($request);
    }
}
