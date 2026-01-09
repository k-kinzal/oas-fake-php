# AGENTS.md

Fake API server library for PHP testing. Intercepts HTTP requests via PHP-VCR and returns fake responses generated from an OpenAPI schema. No real server process is needed — the fake server runs in-process, validating requests and generating schema-compliant responses automatically.

## Supported Versions

- PHP: 8.0 - 8.5

## Architecture

```
OasFake (facade)
  └─ ServerRegistry          # Multi-server lifecycle management
       └─ Server             # Per-server fluent configuration
            └─ Interceptor   # PHP-VCR hook, request/response pipeline
                 ├─ Converter       # VCR ↔ PSR-7 format bridge
                 ├─ Validator       # OpenAPI request/response validation
                 ├─ HandlerMap      # Handler lookup by operationId or path/method
                 │    └─ Handler    # Response strategy (fixed, callback, status)
                 ├─ FakeResponse    # Schema-based response generation
                 └─ Middleware[]    # PSR-15 middleware chain

Schema             # OpenAPI spec wrapper (file/string/object)
OperationLookup    # Indexes operations for fast lookup
FakeRequest        # Schema-based request generation (standalone use)
ParameterFaker     # Generates fake path/query/header parameters
```

Request flow: VCR intercept → Converter → Validator → HandlerMap → Handler/FakeResponse → Middleware → Validator → Converter → VCR response.

Modes: `FAKE` (generate from schema), `RECORD` (capture real responses), `REPLAY` (playback from cassettes).

## Public API

Users interact with `OasFake`, `Server`, `FakeRequest`, and `FakeResponse`. Everything else is internal.

### OasFake + Server — Fake server in tests

```php
// 1. Define a server subclass
class MyServer extends Server {
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
}

// 2. Start and use in tests
OasFake::start(MyServer::class);
$response = $client->get('/pets');  // intercepted, returns fake response
OasFake::stop();

// 3. Override responses per test via configure callback
OasFake::start(MyServer::class, fn (MyServer $s) => $s
    ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Buddy']])
    ->withCallback('createPet', fn ($req, $res) => new Response(201, [], '{}'))
    ->withMiddleware(new MyMiddleware()));
```

Server subclass can also define handler methods directly:

```php
class MyServer extends Server {
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';

    // Method named after operationId → auto-registered as handler
    public function listPets(ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface { ... }

    // Or use #[Route] attribute to map by path/method
    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface { ... }
}
```

### FakeRequest / FakeResponse — Standalone generation

Generate requests/responses from a schema without starting a server.

```php
$schema = Schema::fromFile('openapi.yaml');
$request = FakeRequest::for($schema, 'createPet');
$response = FakeResponse::for($schema, 'listPets');
```

## Commands

```bash
composer test             # Run tests
composer lint             # Static analysis + format check
composer format           # Fix code style
composer format:check     # Check code style without fixing
```
