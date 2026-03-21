<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;
use LogicException;
use OasFake\Exception\ReplayMismatchError;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\VCR;
use VCR\VCRFactory;
use VCR\Videorecorder;

/**
 * Registry that manages multiple Server instances with a shared VCR lifecycle.
 *
 * Routes intercepted requests to the appropriate server based on URL matching.
 */
final class ServerRegistry
{
    /**
     * @var array<string, Server> key=class名
     */
    private array $servers = [];

    /**
     * @var array<string, array{interceptor: Interceptor, mode: string}> key=baseUrl
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
     * The server is started in managed mode where VCR lifecycle is controlled by the registry.
     *
     * @param string $key Unique identifier for the server (typically the class name)
     * @param Server $server The server instance to register
     */
    public function register(string $key, Server $server): void
    {
        if (isset($this->servers[$key])) {
            $this->unregister($key);
        }

        $server->start(managed: true);

        $this->servers[$key] = $server;

        $urls = $server->serverUrls();
        $this->urlsByKey[$key] = $urls;

        $interceptor = $server->interceptor();
        if ($interceptor !== null) {
            $mode = $server->resolveMode();
            foreach ($urls as $url) {
                $this->interceptors[$url] = [
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

        $this->servers[$key]->stop();

        if (isset($this->urlsByKey[$key])) {
            foreach ($this->urlsByKey[$key] as $url) {
                unset($this->interceptors[$url]);
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
            $this->servers[$key]->stop();
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
     * In FAKE mode, delegates to the interceptor's handle method.
     * In RECORD/REPLAY mode, delegates to VCR's Videorecorder.
     * Returns a 502 error if no server matches the request URL.
     *
     * @param VcrRequest $request The intercepted HTTP request
     *
     * @return VcrResponse The response from the matched server or an error response
     */
    public function dispatch(VcrRequest $request): VcrResponse
    {
        $url = $request->getUrl() ?? '';

        foreach ($this->interceptors as $baseUrl => $entry) {
            if (!$this->urlMatchesServer($url, $baseUrl)) {
                continue;
            }

            if ($entry['mode'] === Mode::FAKE) {
                return $entry['interceptor']->handle($request);
            }

            if ($entry['mode'] === Mode::REPLAY) {
                return $this->handleReplayRequest($request);
            }

            // RECORD mode
            /** @var Videorecorder $videorecorder */
            $videorecorder = VCRFactory::get(Videorecorder::class);

            return $videorecorder->handleRequest($request);
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

    private function handleReplayRequest(VcrRequest $request): VcrResponse
    {
        VCR::configure()->enableRequestMatchers(['method', 'url', 'host', 'query_string', 'body', 'post_fields', 'headers']);

        try {
            /** @var Videorecorder $videorecorder */
            $videorecorder = VCRFactory::get(Videorecorder::class);

            return $videorecorder->handleRequest($request);
        } catch (LogicException $e) {
            throw ReplayMismatchError::forRequest($request, $e);
        } finally {
            VCR::configure()
                ->addRequestMatcher('fake_matcher', static fn (): bool => true)
                ->enableRequestMatchers(['fake_matcher']);
        }
    }

    private function urlMatchesServer(string $requestUrl, string $baseUrl): bool
    {
        if ($baseUrl === '/') {
            return true;
        }

        return str_starts_with($requestUrl, $baseUrl);
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
            ->enableLibraryHooks(['curl', 'stream_wrapper'])
            ->addRequestMatcher(
                'fake_matcher',
                static function (): bool {
                    return true;
                },
            );
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
