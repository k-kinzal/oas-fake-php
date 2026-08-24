# AGENTS.md

Fake API server library for PHP testing. Intercepts HTTP requests via PHP-VCR and returns fake responses generated from an OpenAPI schema. No real server process is needed — the fake server runs in-process, validating requests and generating schema-compliant responses automatically.

## Supported Versions

- PHP: 8.0 - 8.5

## Architecture

```
OasFake (facade)
  └─ ServerRegistry          # Multi-server lifecycle management
       └─ Server             # Public fluent API
            ├─ ServerConfiguration  # Mutable settings and environment policy
            ├─ ServerLifecycle      # Registry/interceptor ownership
            └─ Interceptor          # PHP-VCR hook, request/response pipeline
                 ├─ OperationRequestResolver # URL → schema operation
                 ├─ SchemaRequestHandler     # Validation and response selection
                 ├─ HandlerMap               # operationId/path handler lookup
                 │    └─ Handler              # Total response strategy closure
                 ├─ MiddlewarePipeline        # PSR-15 chain
                 ├─ Validator                 # OpenAPI validation
                 └─ Converter                 # VCR ↔ PSR-7 bridge

Schema                     # OpenAPI spec wrapper (file/string/object)
OperationIndexBuilder      # Builds immutable operation metadata
OperationLookup            # Queries indexed operations
FakeDataSource             # Schema + faker policy contract
FakeRequest/FakeResponse   # Standalone schema-based generation
RequestBodyGenerator       # Request payload responsibility
OperationResponseResolver  # Response status/media/schema responsibility
ParameterFaker             # Generates values
ParameterSerializer        # Applies OpenAPI wire styles
```

Request flow: VCR intercept → Converter → Validator → HandlerMap → Handler/FakeResponse → Middleware → Validator → Converter → VCR response.

Modes: `FAKE` (generate from schema), `RECORD` (generate responses and save them to cassettes), `REPLAY` (playback from cassettes).

## Public API

Users interact with `OasFake`, `Server`, `Schema`, `FakeDataContext`, `FakeRequest`, `FakeResponse`, `Handler`, `Route`, `Mode`, and the documented exception types. `Validator` is also documented for standalone validation use.

Pipeline and support classes such as `ServerRegistry`, `Interceptor`, `Converter`, `OperationLookup`, `OperationInfo`, `HandlerMap`, and `ParameterFaker` are available for advanced use, though most tests should use the higher-level facade and server APIs.

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
composer test              # Parallel modern suite
composer test:unit         # Modern unit + doctest suites
composer test:unit:legacy  # PHPUnit 9 suite for PHP 8.0
composer test:coverage     # Strict coverage metadata + XML
composer doctest           # Runnable public PHPDoc examples
composer lint              # All fast toolkit gates below
composer phpstan           # Contracts, checked exceptions, strict types
composer compat            # PHPCompatibility for PHP 8.0+
composer loc-guard         # Size and complexity limits
composer tree-guard        # Repository layout policy
composer scope-guard       # @visibility contracts
composer deptrac           # Architectural dependency direction
composer doc-gen           # API/architecture site in build/docs
composer format            # Fix code style
composer format:check      # Check code style without fixing
```

Mutation testing is intentionally a two-step CI gate: generate `build/infection-coverage`, then run Infection with `infection.json5`. Do not disable mutators, narrow `source.directories`, or add ignore annotations to improve the score; improve the asserted behavior or redesign equivalent code instead.
