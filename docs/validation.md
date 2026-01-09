# Validation

OasFake validates requests and responses against the OpenAPI schema by default. Validation uses [league/openapi-psr7-validator](https://github.com/thephpleague/openapi-psr7-validator) under the hood.

## Automatic Validation

When the server is running, requests are validated before handler resolution and responses are validated before being returned. Invalid requests or responses throw `OasFake\Exception\ValidationException`.

```php
use OasFake\OasFake;

$server = OasFake::start(MyServer::class);

// This will throw ValidationException if the request body
// doesn't match the schema (e.g., missing required fields)
$client->post('/users', [
    'json' => ['name' => 'Alice'], // Missing required 'email' field
]);
```

## Disabling Validation

### Static Properties

```php
final class MyServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static bool $VALIDATE_REQUESTS = false;
    protected static bool $VALIDATE_RESPONSES = false;
}
```

### Fluent API

```php
$server = OasFake::start(MyServer::class, fn ($s) => $s
    ->withRequestValidation(false)
    ->withResponseValidation(false)
);
```

### Environment Variables

```bash
OAS_FAKE_VALIDATE_REQUESTS=false
OAS_FAKE_VALIDATE_RESPONSES=false
```

## ValidationException

`OasFake\Exception\ValidationException` provides details about what failed:

```php
use OasFake\Exception\ValidationException;

try {
    $client->post('/users', ['json' => ['invalid' => 'data']]);
} catch (ValidationException $e) {
    $e->getMessage();          // "Request validation failed for POST /users: ..."
    $e->getValidationError();  // League\OpenAPIValidation\PSR7\Exception\ValidationFailed
}
```

There are two factory methods:
- `ValidationException::forRequest(RequestInterface $request, ValidationFailed $previous)` - request validation failure
- `ValidationException::forResponse(ResponseInterface $response, string $path, string $method, ValidationFailed $previous)` - response validation failure

## Standalone Validator

The `OasFake\Validator` class can be used independently of the server for validating PSR-7 messages:

```php
use OasFake\Schema;
use OasFake\Validator;

$schema = Schema::fromFile(__DIR__ . '/openapi.yaml');
$validator = new Validator($schema);

// Throws ValidationException on failure, returns OperationAddress on success
$operation = $validator->validateRequest($psr7Request);
$validator->validateResponse($operation, $psr7Response);

// Boolean checks (no exceptions)
$validator->isValidRequest($psr7Request);    // true/false
$validator->isValidResponse($operation, $psr7Response); // true/false
```

## FakeRequest

`OasFake\FakeRequest` generates fake HTTP requests from an OpenAPI schema, useful for testing your handlers or validation:

### Creating Requests

```php
use OasFake\FakeRequest;

// By operation ID
$request = FakeRequest::for($server, 'createPet');
$request = FakeRequest::for($schema, 'createPet', ['alwaysFakeOptionals' => true]);

// By path and method
$request = FakeRequest::forPath($server, '/pets/{petId}', 'GET');
$request = FakeRequest::forPath($schema, '/pets', 'POST');
```

The `$source` parameter accepts either a `Server` instance (uses its schema and faker options) or a `Schema` instance.

### Fluent Overrides

```php
$request = FakeRequest::for($server, 'showPetById')
    ->withPathParam('petId', '42')
    ->withQueryParam('fields', 'name,species')
    ->withHeader('Authorization', 'Bearer token123')
    ->withBody('{"name": "Buddy"}');
```

### Accessors

| Method | Returns |
|---|---|
| `method()` | HTTP method (uppercase) |
| `url()` | Full URL with path params and query string applied |
| `body()` | Raw request body (`?string`) |
| `headers()` | `array<string, string>` |
| `pathParams()` | `array<string, string>` |
| `queryParams()` | `array<string, string>` |

### Conversion Methods

```php
$psr7 = $request->toPsr7();   // Psr\Http\Message\ServerRequestInterface
$curl = $request->toCurl();   // curl command string
$array = $request->toArray();  // ['method' => ..., 'url' => ..., 'headers' => ..., 'body' => ...]
```

## FakeResponse

`OasFake\FakeResponse` generates fake HTTP responses from an OpenAPI schema:

### Creating Responses

```php
use OasFake\FakeResponse;

// By operation ID (default status 200)
$response = FakeResponse::for($server, 'listPets');
$response = FakeResponse::for($schema, 'listPets', 404);

// By path and method
$response = FakeResponse::forPath($server, '/pets', 'GET');
$response = FakeResponse::forPath($schema, '/pets/{petId}', 'GET', 200, ['alwaysFakeOptionals' => true]);
```

### Accessors

| Method | Returns |
|---|---|
| `statusCode()` | HTTP status code |
| `headers()` | `array<string, string>` |
| `body()` | Raw response body string |
| `json()` | Decoded JSON body (`mixed`) |

### Conversion Methods

```php
$psr7 = $response->toPsr7();   // Psr\Http\Message\ResponseInterface
$array = $response->toArray();  // ['statusCode' => ..., 'headers' => ..., 'body' => ...]
```

## Schema

`OasFake\Schema` wraps an OpenAPI specification:

```php
use OasFake\Schema;

// Load from file (auto-detects JSON/YAML by extension)
$schema = Schema::fromFile(__DIR__ . '/openapi.yaml');
$schema = Schema::fromFile(__DIR__ . '/openapi.json');

// Parse from string
$schema = Schema::fromString($yamlContent);
$schema = Schema::fromString($jsonContent, 'json');

// From an existing cebe/php-openapi object
$schema = Schema::fromOpenApi($openApiObject);

// Access the underlying OpenApi object
$openApi = $schema->openApi();

// Get server URLs (with variables substituted)
$urls = $schema->serverUrls(); // ['https://api.example.com']
```

## Related

- [Server Configuration](server-configuration.md) - Enable/disable validation settings
- [Environment Variables](environment-variables.md) - Validation env vars
