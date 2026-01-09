# Custom Responses

By default, OasFake generates responses from the OpenAPI schema using a faker. You can override responses for specific operations using handlers.

## By Operation ID

### Static Response

```php
$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withResponse('listPets', 200, [
        ['id' => 1, 'name' => 'Buddy'],
        ['id' => 2, 'name' => 'Max'],
    ])
);
```

### Callback

The callback receives the request and an optional faker-generated default response:

```php
$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withCallback('createPet', function ($request, $response) {
        $body = json_decode((string) $request->getBody(), true);
        return new \GuzzleHttp\Psr7\Response(
            201,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 42, 'name' => $body['name']]),
        );
    })
);
```

### Handler Object

```php
use OasFake\Handler;

$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withHandler('getPet', Handler::response(200, ['id' => 1, 'name' => 'Buddy']))
    ->withHandler('deletePet', Handler::status(204))
    ->withHandler('updatePet', Handler::callback(function ($request, $response) {
        return new \GuzzleHttp\Psr7\Response(200, [], '{"updated": true}');
    }))
);
```

## By Path and Method

When you don't have an `operationId` or want to match by route:

```php
$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withPathResponse('/pets', 'GET', 200, [['id' => 1, 'name' => 'Buddy']])
    ->withPathCallback('/pets/{petId}', 'GET', function ($request, $response) {
        return new \GuzzleHttp\Psr7\Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 1, 'name' => 'Buddy']),
        );
    })
);
```

## Handler Factory Methods

The `OasFake\Handler` class provides three factory methods:

| Method | Description |
|---|---|
| `Handler::response(int $status, array\|string $body, array $headers = [])` | Fixed response with status, body, and optional headers |
| `Handler::callback(callable $callback)` | Callback receiving `(ServerRequestInterface $request, ?ResponseInterface $response)` |
| `Handler::status(int $status)` | Empty response with just a status code |

The `$response` parameter in callbacks is the faker-generated default response (if available). You can use it as a fallback or modify it:

```php
$server->withCallback('listPets', function ($request, $response) {
    // Use the faker-generated response but override the status
    return $response?->withStatus(200) ?? new Response(200);
});
```

## Declarative Server Methods

Public methods on a `Server` subclass are automatically registered as handlers. Methods are matched by name to `operationId`:

```php
use OasFake\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

final class PetStoreServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';

    // Matched to operationId: listPets
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new Response(200, ['Content-Type' => 'application/json'], '[]');
    }

    // Matched to operationId: createPet
    public function createPet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        return new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'id' => 42,
            'name' => $body['name'] ?? 'Unknown',
        ]));
    }
}
```

## Route Attribute

Use `#[Route]` to map a method to a specific HTTP path and method instead of an operation ID:

```php
use OasFake\Route;
use OasFake\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MyServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';

    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new \GuzzleHttp\Psr7\Response(204);
    }
}
```

## Handler Resolution Priority

When a request is received, handlers are resolved in this order:

1. **Operation ID handler** - registered via `withHandler()`, `withResponse()`, `withCallback()`, or a declarative method matching the `operationId`
2. **Path/method handler** - registered via `withPathResponse()`, `withPathCallback()`, or a `#[Route]` method
3. **Faker** - auto-generated response from the OpenAPI schema

## Related

- [Server Configuration](server-configuration.md) - Faker options for generated responses
- [Validation](validation.md) - How responses are validated
