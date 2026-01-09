# AGENTS.md

## Project Overview

PHP testing library that intercepts HTTP requests using PHP-VCR, validates against OpenAPI schemas, and generates fake responses.

## Stack

- PHP 8.2+
- PHPUnit 11
- PHPStan level max
- PHP-CS-Fixer (PSR-12)

## Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
XDEBUG_MODE=coverage composer test:coverage

# Static analysis
composer phpstan

# Code style check
composer cs-check

# Code style fix
composer cs-fix

# All quality checks
composer quality
```

## Project Structure

```
src/
├── Config/Configuration.php      # Schema loading & settings
├── Exception/                    # Exception classes
├── Http/                         # VCR↔PSR-7 converters
├── Response/
│   ├── FakeResponseGenerator.php # Generates fake responses
│   └── CallbackRegistry.php      # Custom callback management
├── Server/
│   ├── Server.php                # Server interface
│   ├── FakeServer.php            # Base class for class-based server definition
│   ├── Callback.php              # Attribute for path+method callbacks
│   ├── SchemaSetting.php         # Schema configuration trait
│   ├── ModeSetting.php           # Mode configuration trait
│   ├── ValidationSetting.php     # Validation configuration trait
│   ├── CassetteSetting.php       # Cassette path configuration trait
│   └── FakerSetting.php          # Faker options configuration trait
├── Validation/                   # Request/response validators
├── Vcr/
│   ├── Mode.php                  # Enum: record/replay/passthrough
│   ├── VcrManager.php            # VCR lifecycle
│   └── FakeRequestHandler.php    # Request processing
└── OasFake.php                   # Main facade (singleton)
```

## Code Style

- Strict types: `declare(strict_types=1);`
- Namespace: `OasFakePHP\`
- Use `final` for classes not designed for inheritance
- Use `readonly` for immutable properties
- PHPDoc only when types cannot be expressed in PHP

```php
<?php

declare(strict_types=1);

namespace OasFakePHP\Example;

final class Example
{
    public function __construct(
        private readonly string $value,
    ) {
    }
}
```

## Testing

- Test files in `tests/Unit/` mirror `src/` structure
- Use `#[CoversClass()]` attribute
- Fixtures in `tests/Fixtures/`

```php
#[CoversClass(Example::class)]
final class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        self::assertSame('expected', $actual);
    }
}
```

## FakeServer Pattern

The `FakeServer` class follows a testcontainers-php inspired pattern:

- **Static properties** define default configuration (e.g., `$SCHEMA_FILE`, `$MODE`)
- **Traits** provide settings (SchemaSetting, ModeSetting, etc.)
- **Fluent methods** allow runtime overrides (e.g., `withMode()`, `withSchemaFile()`)
- **Public methods** matching operationId are auto-registered as callbacks
- **#[Callback] attribute** registers path+method callbacks

Resolution order: Instance value → Static property → Default

```php
class MyServer extends FakeServer
{
    protected static string $SCHEMA_FILE = './api.yaml';
    protected static Mode $MODE = Mode::REPLAY;

    // Auto-registered as callback for operationId "listUsers"
    public function listUsers($request, $response): ResponseInterface { ... }

    // Auto-registered for DELETE /users/{id}
    #[Callback(path: '/users/{id}', method: 'DELETE')]
    public function deleteUser($request, $response): ResponseInterface { ... }
}
```

## Boundaries

- Never modify `vendor/` directory
- Never commit `.env` or credential files
- Always run `composer phpstan` before committing
- Always run `composer cs-fix` before committing
