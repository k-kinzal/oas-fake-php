# Server Configuration

Servers can be configured via static properties on a subclass, the fluent API, or environment variables. These approaches can be combined freely.

## Configuration Approaches

### Static Properties (Subclass)

```php
use OasFake\Server;

final class MyServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $MODE = 'fake';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;
    protected static array $FAKER_OPTIONS = [
        'alwaysFakeOptionals' => true,
        'minItems' => 1,
        'maxItems' => 5,
    ];
}
```

### Fluent API (Configure Callback)

```php
use OasFake\OasFake;
use OasFake\Mode;

$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withMode(Mode::RECORD)
    ->withCassettePath('./fixtures/cassettes')
    ->withRequestValidation(false)
    ->withFakerOptions(['alwaysFakeOptionals' => true])
);
```

### Fluent API (Instance)

```php
use OasFake\OasFake;
use OasFake\Server;
use OasFake\Mode;

$server = (new Server())
    ->withSchema(__DIR__ . '/openapi.yaml')
    ->withMode(Mode::FAKE)
    ->withRequestValidation(true)
    ->withResponseValidation(true);

OasFake::start($server);
```

## Configuration Options

| Static Property | Fluent Method | Description | Default |
|---|---|---|---|
| `$SCHEMA` | `withSchema(string)` | Path to OpenAPI JSON or YAML file | `''` (required) |
| `$MODE` | `withMode(string)` | Operating mode | `'fake'` |
| `$CASSETTE_PATH` | `withCassettePath(string)` | Directory for cassette files | `'./cassettes'` |
| `$VALIDATE_REQUESTS` | `withRequestValidation(bool)` | Validate requests against schema | `true` |
| `$VALIDATE_RESPONSES` | `withResponseValidation(bool)` | Validate responses against schema | `true` |
| `$FAKER_OPTIONS` | `withFakerOptions(array)` | Options for fake data generation | `[]` |
| `middleware()` | `withMiddleware(MiddlewareInterface)` | PSR-15 middleware pipeline | `[]` |

## Operating Modes

The `OasFake\Mode` class defines three operating modes:

| Mode | Value | Description |
|---|---|---|
| `Mode::FAKE` | `'fake'` | Intercept requests and return generated/stubbed responses (default) |
| `Mode::RECORD` | `'record'` | Generate responses and record them as cassettes for later replay |
| `Mode::REPLAY` | `'replay'` | Replay previously recorded cassette responses |

```php
use OasFake\Mode;

// Constants
Mode::FAKE;
Mode::RECORD;
Mode::REPLAY;

// Parse from string (case-insensitive)
Mode::fromString('record'); // Mode::RECORD

// Resolve from OAS_FAKE_MODE env var (falls back to FAKE)
Mode::fromEnvironment();
```

## Configuration Resolution Order

Each setting is resolved with the following priority (highest first):

1. **Environment variable** (e.g., `OAS_FAKE_MODE`)
2. **Fluent API** (e.g., `withMode(Mode::RECORD)`)
3. **Static property** (e.g., `protected static string $MODE = 'fake'`)

This allows tests to override configuration without changing code:

```bash
# Override mode in CI
OAS_FAKE_MODE=record composer test
```

## Faker Options

The `$FAKER_OPTIONS` array (or `withFakerOptions()`) controls how fake response data is generated from the OpenAPI schema:

| Option | Type | Description |
|---|---|---|
| `alwaysFakeOptionals` | `bool` | Generate values for optional properties (default: only required) |
| `minItems` | `int` | Minimum number of items in generated arrays |
| `maxItems` | `int` | Maximum number of items in generated arrays |

```php
// Via static property
protected static array $FAKER_OPTIONS = [
    'alwaysFakeOptionals' => true,
    'minItems' => 2,
    'maxItems' => 5,
];

// Via fluent API
$server->withFakerOptions([
    'alwaysFakeOptionals' => true,
    'minItems' => 2,
    'maxItems' => 5,
]);
```

## Related

- [Environment Variables](environment-variables.md) - Full list of env vars
- [Custom Responses](custom-responses.md) - Override responses for specific operations
- [Middleware](middleware.md) - PSR-15 middleware configuration
