<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\Handler;
use OasFake\HandlerMap;
use OasFake\OperationLookup;
use OasFake\OperationResponder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationResponder::class)]
final class OperationResponderTest extends TestCase
{
    public function testRespondUsesRegisteredHandler(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationLookup($schema))->findByOperationId('listPets');
        $handlers = new HandlerMap();
        $handlers->forOperation('listPets', Handler::response(200, [['id' => 1, 'name' => 'Handled']]));

        $response = (new OperationResponder($schema, $handlers, []))->respond(
            new ServerRequest('GET', 'https://api.petstore.example.com/pets'),
            '/pets',
            'GET',
            $operation,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Handled', (string) $response->getBody());
    }

    public function testRespondReturnsErrorWhenOperationCannotBeResolved(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');

        $response = (new OperationResponder($schema, new HandlerMap(), []))->respond(
            new ServerRequest('GET', 'https://api.petstore.example.com/unknown'),
            '/unknown',
            'GET',
            null,
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Could not resolve operation', (string) $response->getBody());
    }
}
