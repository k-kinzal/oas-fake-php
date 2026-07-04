<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;
use Throwable;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;
use VCR\VCRFactory;

/**
 * Registry that manages multiple Server instances with a shared VCR lifecycle.
 *
 * Routes intercepted requests to the appropriate server based on URL matching.
 */
final class ServerRegistry
{
    /**
     * @var array<string, Server> key=class name
     */
    private array $servers = [];

    /**
     * @var array<string, list<array{key: string, interceptor: Interceptor, mode: string}>> key=baseUrl
     */
    private array $interceptors = [];

    /**
     * @var array<string, list<string>> key=serverKey, value=baseUrls
     */
    private array $urlsByKey = [];

    private bool $vcrActive = false;

    /**
     * Register a server under the given key, replacing any existing registration.
     *
     * The server's interceptor is built but VCR lifecycle is managed by the registry.
     *
     * @param string $key Unique identifier for the server (typically the class name)
     * @param Server $server The server instance to register
     */
    public function register(string $key, Server $server): void
    {
        if (isset($this->servers[$key])) {
            $this->unregister($key);
        }

        $server->registerInRegistry($this, $key);

        try {
            $server->buildInterceptor();
        } catch (Throwable $exception) {
            $server->unregisterFromRegistry($this, $key);

            throw $exception;
        }

        $this->servers[$key] = $server;

        $urls = $server->serverUrls();
        $this->urlsByKey[$key] = $urls;

        $interceptor = $server->interceptor();
        if ($interceptor !== null) {
            $mode = $server->resolveMode();
            foreach ($urls as $url) {
                $this->interceptors[$url] ??= [];
                $this->interceptors[$url][] = [
                    'key' => $key,
                    'interceptor' => $interceptor,
                    'mode' => $mode,
                ];
            }
        }

        $this->ensureVcrActive();
    }

    /**
     * Unregister and stop the server with the given key.
     *
     * @param string $key The server key to unregister
     */
    public function unregister(string $key): void
    {
        if (!isset($this->servers[$key])) {
            return;
        }

        $this->servers[$key]->unregisterFromRegistry($this, $key);

        if (isset($this->urlsByKey[$key])) {
            foreach ($this->urlsByKey[$key] as $url) {
                $this->removeInterceptorEntry($url, $key);
            }
            unset($this->urlsByKey[$key]);
        }

        unset($this->servers[$key]);

        if ($this->servers === []) {
            $this->deactivateVcr();
        }
    }

    /**
     * Stop and unregister all servers.
     */
    public function unregisterAll(): void
    {
        foreach (array_keys($this->servers) as $key) {
            $this->servers[$key]->unregisterFromRegistry($this, $key);
        }

        $this->servers = [];
        $this->interceptors = [];
        $this->urlsByKey = [];

        $this->deactivateVcr();
    }

    /**
     * Retrieve a registered server by key.
     *
     * @param string $key The server key to look up
     *
     * @return Server|null The server instance, or null if not found
     */
    public function get(string $key): ?Server
    {
        return $this->servers[$key] ?? null;
    }

    /**
     * Check whether the registry has no servers registered.
     *
     * @return bool True if no servers are registered
     */
    public function isEmpty(): bool
    {
        return $this->servers === [];
    }

    /**
     * Dispatch an intercepted request to the matching server's interceptor.
     *
     * Routes to handle() for FAKE/RECORD modes, replay() for REPLAY mode.
     * Returns a 502 error if no server matches the request URL.
     *
     * @param VcrRequest $request The intercepted HTTP request
     *
     * @return VcrResponse The response from the matched server or an error response
     */
    public function dispatch(VcrRequest $request): VcrResponse
    {
        $url = $request->getUrl() ?? '';
        $match = null;

        foreach ($this->interceptors as $baseUrl => $entries) {
            $specificity = $this->urlMatchSpecificity($url, $baseUrl);
            if ($specificity === null) {
                continue;
            }

            $entry = $entries[count($entries) - 1] ?? null;
            if ($entry === null) {
                continue;
            }

            if ($match === null || $specificity > $match['specificity']) {
                $match = [
                    'specificity' => $specificity,
                    'entry' => $entry,
                ];
            }
        }

        if ($match !== null) {
            $entry = $match['entry'];
            if ($entry['mode'] === Mode::REPLAY) {
                return $entry['interceptor']->replay($request);
            }

            return $entry['interceptor']->handle($request);
        }

        $converter = new Converter();

        return $converter->psr7ToVcrResponse(
            new Response(
                502,
                ['Content-Type' => 'application/json'],
                (string) json_encode(['error' => 'No OasFake server registered for: ' . $url]),
            ),
        );
    }

    private function removeInterceptorEntry(string $url, string $key): void
    {
        if (!isset($this->interceptors[$url])) {
            return;
        }

        $entries = [];
        foreach ($this->interceptors[$url] as $entry) {
            if ($entry['key'] !== $key) {
                $entries[] = $entry;
            }
        }

        if ($entries === []) {
            unset($this->interceptors[$url]);

            return;
        }

        $this->interceptors[$url] = $entries;
    }

    private function urlMatchSpecificity(string $requestUrl, string $baseUrl): ?int
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
     * @param array{scheme?: string, host?: string, port?: int|string, path?: string} $url
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

    private function pathPrefixSpecificity(string $requestPath, string $basePath): ?int
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

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        $normalized = rtrim($normalized, '/');

        return $normalized === '' ? '/' : $normalized;
    }

    private function ensureVcrActive(): void
    {
        if ($this->vcrActive) {
            return;
        }

        $this->configureVcr();
        VCR::turnOn();
        VCR::insertCassette('oas-fake-registry');
        $this->registerDispatchHook();

        $this->vcrActive = true;
    }

    private function deactivateVcr(): void
    {
        if (!$this->vcrActive) {
            return;
        }

        VCR::turnOff();
        $this->vcrActive = false;
    }

    private function configureVcr(): void
    {
        VCR::configure()
            ->setCassettePath(sys_get_temp_dir())
            ->setStorage('json')
            ->setMode('none')
            ->enableLibraryHooks(['curl', 'stream_wrapper']);
    }

    private function registerDispatchHook(): void
    {
        $handler = fn (VcrRequest $request): VcrResponse => $this->dispatch($request);

        foreach (VCR::configure()->getLibraryHooks() as $hookClass) {
            /** @var \VCR\LibraryHooks\LibraryHook $hook */
            $hook = VCRFactory::get($hookClass);
            $hook->disable();
            $hook->enable($handler);
        }
    }
}
