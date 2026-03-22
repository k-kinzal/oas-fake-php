# Getting Started

## Requirements

- PHP 8.0 or higher
- ext-curl

## Installation

```bash
composer require --dev k-kinzal/oas-fake-php
```

## Quick Start

**1. Define a server class** that points to your OpenAPI schema:

```php
<?php

declare(strict_types=1);

use OasFake\Server;

final class PetStoreServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
}
```

**2. Start the fake server** in your test:

```php
use OasFake\OasFake;

// Start intercepting HTTP requests
$server = OasFake::start(PetStoreServer::class);

// All HTTP requests to URLs defined in the schema now return fake responses
$client = new GuzzleHttp\Client(['base_uri' => 'https://petstore.example.com']);
$response = $client->get('/pets/1');
// Returns a response generated from your OpenAPI schema

// Stop intercepting
OasFake::stop();
```

## How It Works

```
Your Code --> HTTP Request --> OasFake Interception --> Processing --> Fake Response
                                                           |
                                                 1. Validate request against schema
                                                 2. Resolve handler or generate fake response
                                                 3. Run PSR-15 middleware
                                                 4. Validate response against schema
                                                 5. Return response to caller
```

OasFake intercepts HTTP requests by hooking into curl and stream wrappers. Intercepted requests are matched against your OpenAPI schema, validated, and answered with either a custom handler response or an auto-generated fake response based on the schema definition.

## Next Steps

- [Server Configuration](server-configuration.md) - Modes, validation, faker options
- [Custom Responses](custom-responses.md) - Stub specific endpoints
- [Validation](validation.md) - Request/response validation and `FakeRequest`/`FakeResponse`
- [Middleware](middleware.md) - PSR-15 middleware pipeline
- [Environment Variables](environment-variables.md) - CI/CD and env var configuration
