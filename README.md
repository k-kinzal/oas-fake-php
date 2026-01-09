# OAS Fake PHP

[![CI](https://github.com/k-kinzal/oas-fake-php/actions/workflows/ci.yml/badge.svg)](https://github.com/k-kinzal/oas-fake-php/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)

OpenAPI schema-driven fake API server for PHP testing. Define your API contract once, and automatically get a working fake server that generates valid responses, validates requests, and catches schema violations in your tests.

## Features

- Automatic fake response generation from OpenAPI schema definitions
- Request/response validation against the OpenAPI schema
- Custom response stubs and callbacks per operation
- Declarative server classes with handler methods mapped by `operationId`
- Three operating modes: FAKE, RECORD, REPLAY
- PSR-15 middleware support

## Requirements

- PHP 8.0+
- ext-curl

## Installation

```bash
composer require --dev k-kinzal/oas-fake-php
```

## Usage

### Basic

Define a server pointing to your OpenAPI schema and start intercepting:

```php
use OasFake\OasFake;
use OasFake\Server;

final class PetStoreServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
}

// Start intercepting HTTP requests
$server = OasFake::start(PetStoreServer::class);

// All HTTP requests now return responses generated from the schema
$client = new GuzzleHttp\Client(['base_uri' => 'https://petstore.example.com']);
$response = $client->get('/pets/1');

// Stop intercepting
OasFake::stop();
```

### Custom Responses

Override responses for specific operations using the configure callback:

```php
use OasFake\OasFake;
use OasFake\Handler;

$server = OasFake::start(PetStoreServer::class, fn ($s) => $s
    ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Buddy']])
    ->withCallback('createPet', function ($request, $response) {
        $body = json_decode((string) $request->getBody(), true);
        return new \GuzzleHttp\Psr7\Response(201, [], json_encode([
            'id' => 42,
            'name' => $body['name'],
        ]));
    })
    ->withHandler('deletePet', Handler::status(204))
);
```

### Declarative Server

Public methods on a `Server` subclass are automatically registered as handlers, matched by `operationId`:

```php
use OasFake\Server;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PetStoreServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';

    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            ['id' => 1, 'name' => 'Buddy'],
        ]));
    }

    #[\OasFake\Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new Response(204);
    }
}
```

### Configuration

```php
use OasFake\OasFake;
use OasFake\Mode;

$server = OasFake::start(PetStoreServer::class, fn ($s) => $s
    ->withMode(Mode::RECORD)  // or ->withMode('record')
    ->withCassettePath('./fixtures/cassettes')
    ->withRequestValidation(false)
    ->withResponseValidation(false)
    ->withFakerOptions(['alwaysFakeOptionals' => true, 'minItems' => 2, 'maxItems' => 5])
    ->withMiddleware(new MyMiddleware())
);
```

All settings can also be overridden via environment variables:

```bash
OAS_FAKE_MODE=record OAS_FAKE_VALIDATE_REQUESTS=false composer test
```

## License

[MIT](LICENSE)
