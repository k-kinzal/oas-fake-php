<?php

declare(strict_types=1);

namespace OasFake;

use GuzzleHttp\Psr7\Response;
use OasFake\Exception\ServerStateException;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

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

    private InterceptorRouter $router;

    private VcrLifecycle $vcrLifecycle;

    /**
     * Create an empty registry and its process-wide VCR lifecycle owner.
     */
    public function __construct()
    {
        $this->router = new InterceptorRouter(new ServerUrlMatcher());
        $this->vcrLifecycle = new VcrLifecycle();
    }

    /**
     * Register a server under the given key, replacing any existing registration.
     *
     * The server's interceptor is built but VCR lifecycle is managed by the registry.
     *
     * @param string $key Unique identifier for the server (typically the class name)
     * @param Server $server The server instance to register
     *
     * @throws ServerStateException when another registry owns the server
     */
    public function register(string $key, Server $server): void
    {
        $server->assertCanRegisterInRegistry($this, $key);

        if (isset($this->servers[$key])) {
            $this->unregister($key);
        }

        $server->buildInterceptor();
        $server->registerInRegistry($this, $key);

        $this->servers[$key] = $server;

        $interceptor = $server->interceptor();
        if ($interceptor !== null) {
            $this->router->add($key, $server->serverUrls(), $interceptor, $server->resolveMode());
        }

        $this->vcrLifecycle->activate(fn (VcrRequest $request): VcrResponse => $this->dispatch($request));
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

        $this->router->remove($key);
        unset($this->servers[$key]);

        if ($this->servers === []) {
            $this->vcrLifecycle->deactivate();
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
        $this->router->clear();

        $this->vcrLifecycle->deactivate();
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
        $response = $this->router->dispatch($request);
        if ($response !== null) {
            return $response;
        }

        $converter = new Converter();
        $url = $request->getUrl() ?? '';

        return $converter->psr7ToVcrResponse(
            new Response(
                502,
                ['Content-Type' => 'application/json'],
                (string) json_encode(['error' => 'No OasFake server registered for: ' . $url]),
            ),
        );
    }
}
