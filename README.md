# OAS Fake PHP

[![CI](https://github.com/oasfakephp/oas-fake-php/actions/workflows/ci.yml/badge.svg)](https://github.com/oasfakephp/oas-fake-php/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)

A PHP testing library that intercepts HTTP requests, validates against OpenAPI schemas, and generates fake responses using PHP-VCR.

## Overview

OAS Fake PHP wraps PHP-VCR to intercept HTTP communications (curl/Guzzle) and provides:

- Request validation against OpenAPI schema
- Automatic fake response generation based on schema definitions
- Response validation against OpenAPI schema
- Custom response callbacks for specific test scenarios
- Multiple modes: record, replay, and passthrough

### How It Works

**Request Interception** - All HTTP requests are captured by PHP-VCR hooks:

```
Your Code → HTTP Request → OAS Fake PHP → Fake Response
                              ↓
                    1. Validate request against schema
                    2. Generate fake response (or use callback)
                    3. Validate response against schema
                    4. Return fake response
```

**Fake Response Generation** - Responses are automatically generated from your OpenAPI schema using Faker:

```yaml
# OpenAPI Schema
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
```

```json
// Generated Response
{
  "id": 42,
  "name": "Lorem ipsum"
}
```

## Requirements

- PHP 8.2 or higher
- ext-curl or stream_wrapper support

## Installation

```bash
composer require --dev oasfakephp/oas-fake-php
```

## Usage

### Basic Example

```php
use OasFakePHP\OasFake;
use OasFakePHP\Vcr\Mode;

// Configure with OpenAPI schema
OasFake::fromYamlFile('./openapi.yaml')
    ->setMode(Mode::REPLAY)
    ->enableRequestValidation()
    ->enableResponseValidation();

// Start interception
OasFake::start();

// All HTTP requests now return fake responses
$client = new GuzzleHttp\Client(['base_uri' => 'https://api.example.com']);
$response = $client->get('/pets/123');
// Returns: {"id": 42, "name": "Lorem ipsum"}

// Stop interception
OasFake::stop();
```

### Class-Based Server Definition

Define your fake server as a class with static properties for reusable, self-contained test fixtures:

```php
use OasFakePHP\OasFake;
use OasFakePHP\Server\FakeServer;
use OasFakePHP\Server\Callback;
use OasFakePHP\Vcr\Mode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class PetStoreServer extends FakeServer
{
    // Static configuration
    protected static string $SCHEMA_FILE = './openapi.yaml';
    protected static Mode $MODE = Mode::REPLAY;
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;

    // Callback method (matches operationId)
    public function getPetById(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 123,
            'name' => 'Fluffy',
        ]));
    }

    // Callback with path and method via attribute
    #[Callback(path: '/pets', method: 'POST')]
    public function createPet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        return new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'id' => 999,
            'name' => $body['name'] ?? 'New Pet',
        ]));
    }
}

// Start with class name
OasFake::start(PetStoreServer::class);

// Or with instance (allows fluent overrides)
$server = (new PetStoreServer())
    ->withMode(Mode::PASSTHROUGH)
    ->withCassettePath('/custom/path');
OasFake::start($server);

// Use as normal
$client = new GuzzleHttp\Client(['base_uri' => 'https://api.example.com']);
$response = $client->get('/pets/123');

OasFake::stop();
```

### Custom Response Callbacks

```php
use GuzzleHttp\Psr7\Response;

// Register callback by operationId
OasFake::registerCallback('getPetById', function ($request, $defaultResponse) {
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['id' => 123, 'name' => 'Custom Pet'])
    );
});

// Register callback by path and method
OasFake::registerCallbackForPath('/pets/{petId}', 'GET', function ($request, $defaultResponse) {
    // Modify the auto-generated response
    $body = json_decode((string) $defaultResponse->getBody(), true);
    $body['name'] = 'Modified Name';

    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode($body)
    );
});
```

### Loading Schema from JSON File

```php
OasFake::fromJsonFile('./openapi.json')
    ->setMode(Mode::REPLAY);
```

### Loading Schema from String

```php
// From JSON string
$json = '{"openapi": "3.0.0", "info": {"title": "API", "version": "1.0.0"}, "paths": {}}';
OasFake::fromJsonString($json);

// From YAML string
$yaml = file_get_contents('https://example.com/openapi.yaml');
OasFake::fromYamlString($yaml);
```

### Environment Variable Configuration

```bash
# Set mode via environment variable
export OAS_FAKE_VCR_MODE=replay  # or: record, passthrough
```

```php
// Mode is automatically read from environment
OasFake::fromYamlFile('./openapi.yaml');
// Mode defaults to OAS_FAKE_VCR_MODE or REPLAY
```

## Configuration

```php
use OasFakePHP\OasFake;
use OasFakePHP\Vcr\Mode;

// Start with schema source (required)
OasFake::fromYamlFile('./openapi.yaml')
    // VCR mode (default: from env or REPLAY)
    ->setMode(Mode::REPLAY)

    // Cassette path for record mode (default: sys_get_temp_dir())
    ->setCassettePath('./fixtures/cassettes')

    // Validation settings (default: both enabled)
    ->enableRequestValidation(true)
    ->enableResponseValidation(true)

    // Faker options for response generation
    ->setFakerOptions([
        'alwaysFakeOptionals' => true,
        'minItems' => 2,
        'maxItems' => 5,
    ]);
```

### Configuration Options

| Method | Description |
|--------|-------------|
| `fromYamlFile(string $path)` | Load OpenAPI schema from YAML file |
| `fromJsonFile(string $path)` | Load OpenAPI schema from JSON file |
| `fromJsonString(string $json)` | Load OpenAPI schema from JSON string |
| `fromYamlString(string $yaml)` | Load OpenAPI schema from YAML string |
| `setMode(Mode $mode)` | Set VCR mode: RECORD, REPLAY, or PASSTHROUGH |
| `setCassettePath(string $path)` | Directory for cassette files (record mode) |
| `enableRequestValidation(bool)` | Enable/disable request validation |
| `enableResponseValidation(bool)` | Enable/disable response validation |
| `setFakerOptions(array)` | Options for fake data generation |

### VCR Modes

| Mode | Description |
|------|-------------|
| `REPLAY` | Intercept requests and return fake responses (default) |
| `RECORD` | Forward requests to real server and record responses |
| `PASSTHROUGH` | Validate only, forward to real server without recording |

## API Reference

### OasFake (Facade)

```php
// Configuration
OasFake::configure(): Configuration

// Callbacks
OasFake::registerCallback(string $operationId, callable $callback): void
OasFake::registerCallbackForPath(string $path, string $method, callable $callback): void

// Lifecycle
OasFake::start(Server|string|null $server = null): void  // Pass class name or instance
OasFake::stop(): void
OasFake::reset(): void
OasFake::isRunning(): bool

// Accessors
OasFake::getConfiguration(): Configuration
OasFake::getCallbackRegistry(): CallbackRegistry
OasFake::getServer(): ?Server  // Get current server instance
```

### FakeServer (Class-Based Definition)

```php
// Static properties (override in subclass)
protected static ?string $SCHEMA_FILE = null;
protected static ?string $SCHEMA_STRING = null;
protected static ?string $SCHEMA_FORMAT = null;  // 'yaml' or 'json'
protected static ?Mode $MODE = null;
protected static ?bool $VALIDATE_REQUESTS = null;
protected static ?bool $VALIDATE_RESPONSES = null;
protected static ?string $CASSETTE_PATH = null;
protected static ?array $FAKER_OPTIONS = null;

// Fluent configuration
$server->withSchemaFile(string $path): static
$server->withSchemaString(string $content, string $format = 'yaml'): static
$server->withSchema(OpenApi $schema): static
$server->withMode(Mode $mode): static
$server->withRequestValidation(bool $enable = true): static
$server->withResponseValidation(bool $enable = true): static
$server->withCassettePath(string $path): static
$server->withFakerOptions(array $options): static

// Callback registration
$server->registerCallback(string $operationId, callable $callback): static
$server->registerCallbackForPath(string $path, string $method, callable $callback): static

// Lifecycle
$server->start(): void
$server->stop(): void
$server->isRunning(): bool
```

### Callback Attribute

```php
use OasFakePHP\Server\Callback;

// Use on methods in FakeServer subclass
#[Callback(path: '/pets/{petId}', method: 'DELETE')]
public function deletePet($request, $response): ResponseInterface
{
    return new Response(204);
}
```

## Dependencies

| Library | Purpose |
|---------|---------|
| [php-vcr/php-vcr](https://github.com/php-vcr/php-vcr) | HTTP request interception |
| [league/openapi-psr7-validator](https://github.com/thephpleague/openapi-psr7-validator) | Request/response validation |
| [canvural/php-openapi-faker](https://github.com/canvural/php-openapi-faker) | Fake response generation |
| [cebe/php-openapi](https://github.com/cebe/php-openapi) | OpenAPI schema parsing |

## License

MIT License. See [LICENSE](LICENSE) for details.
