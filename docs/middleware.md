# Middleware

OasFake supports [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware in the request processing pipeline.

## Registration

### Fluent API

```php
use OasFake\OasFake;

$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withMiddleware(new LoggingMiddleware())
    ->withMiddleware(new RequestIdMiddleware())
);
```

### Static Declaration

Override the `middleware()` method in your server subclass:

```php
use OasFake\Server;
use Psr\Http\Server\MiddlewareInterface;

final class MyServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';

    protected static function middleware(): array
    {
        return [
            new LoggingMiddleware(),
            new RequestIdMiddleware(),
        ];
    }
}
```

### Combining Both

Static middleware and fluent middleware are merged. Static middleware runs first, followed by fluent middleware in the order they were added.

```php
// Static middleware: [LoggingMiddleware]
// Fluent middleware: [RequestIdMiddleware, RateLimitMiddleware]
// Execution order: LoggingMiddleware -> RequestIdMiddleware -> RateLimitMiddleware -> Handler
```

## Writing Middleware

Implement `Psr\Http\Server\MiddlewareInterface`:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        return $response->withHeader('X-Request-Id', bin2hex(random_bytes(8)));
    }
}
```

## Examples

### Request Recording

Track all intercepted requests for assertions in tests:

```php
final class RequestRecorderMiddleware implements MiddlewareInterface
{
    private array $calls = [];

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $this->calls[] = [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
        ];

        return $handler->handle($request);
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}

// Usage in test
$recorder = new RequestRecorderMiddleware();
$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withMiddleware($recorder)
);

$client->get('/pets');
$client->post('/pets', ['json' => ['name' => 'Buddy']]);

assert($recorder->getCalls() === [
    ['method' => 'GET', 'path' => '/pets'],
    ['method' => 'POST', 'path' => '/pets'],
]);
```

### Response Header Injection

```php
final class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
    }
}
```

## Related

- [Server Configuration](server-configuration.md) - Middleware configuration options
- [Getting Started](getting-started.md) - Request interception flow
