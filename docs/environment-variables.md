# Environment Variables

Environment variables override both static properties and fluent API settings, making them useful for CI/CD pipelines and local development overrides.

## Available Variables

| Variable | Values | Default | Description |
|---|---|---|---|
| `OAS_FAKE_MODE` | `fake`, `record`, `replay` | `fake` | Operating mode |
| `OAS_FAKE_CASSETTE_PATH` | directory path | `./cassettes` | Directory for cassette files |
| `OAS_FAKE_VALIDATE_REQUESTS` | `true`, `false` | `true` | Enable/disable request validation |
| `OAS_FAKE_VALIDATE_RESPONSES` | `true`, `false` | `true` | Enable/disable response validation |

## Precedence Rules

Each setting is resolved with the following priority (highest first):

1. **Environment variable**
2. **Fluent API** (`withMode()`, `withCassettePath()`, etc.)
3. **Static property** (`$MODE`, `$CASSETTE_PATH`, etc.)

For example, if `OAS_FAKE_MODE=record` is set, it overrides both `$MODE = 'fake'` on the class and any `withMode()` call.

## CI/CD Usage

### Record cassettes in CI

```bash
OAS_FAKE_MODE=record composer test
```

### Replay cassettes without validation

```bash
OAS_FAKE_MODE=replay OAS_FAKE_VALIDATE_REQUESTS=false composer test
```

### Disable validation for fast local runs

```bash
OAS_FAKE_VALIDATE_REQUESTS=false OAS_FAKE_VALIDATE_RESPONSES=false composer test
```

### PHPUnit configuration

Set environment variables in `phpunit.xml`:

```xml
<phpunit>
    <php>
        <env name="OAS_FAKE_MODE" value="fake"/>
        <env name="OAS_FAKE_VALIDATE_REQUESTS" value="true"/>
        <env name="OAS_FAKE_VALIDATE_RESPONSES" value="true"/>
    </php>
</phpunit>
```

## Related

- [Server Configuration](server-configuration.md) - Full configuration options
