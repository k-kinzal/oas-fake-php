<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Handler;
use OasFake\Server;
use OasFake\ServerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;

#[CoversClass(ServerRegistry::class)]
final class ServerRegistryTest extends TestCase
{
    private ServerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ServerRegistry();
    }

    protected function tearDown(): void
    {
        $this->registry->unregisterAll();
    }

    public function testIsEmptyByDefault(): void
    {
        self::assertTrue($this->registry->isEmpty());
    }

    public function testRegisterAndGet(): void
    {
        $server = $this->createMock(Server::class);

        $this->registry->register('TestServer', $server);

        self::assertFalse($this->registry->isEmpty());
        self::assertSame($server, $this->registry->get('TestServer'));
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        self::assertNull($this->registry->get('Unknown'));
    }

    public function testUnregister(): void
    {
        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('stop');

        $this->registry->register('TestServer', $server);
        $this->registry->unregister('TestServer');

        self::assertTrue($this->registry->isEmpty());
        self::assertNull($this->registry->get('TestServer'));
    }

    public function testUnregisterNonExistentDoesNothing(): void
    {
        $this->registry->unregister('Unknown');
        $this->addToAssertionCount(1);
    }

    public function testUnregisterAll(): void
    {
        $server1 = $this->createMock(Server::class);
        $server1->expects(self::once())->method('stop');
        $server2 = $this->createMock(Server::class);
        $server2->expects(self::once())->method('stop');

        $this->registry->register('Server1', $server1);
        $this->registry->register('Server2', $server2);

        $this->registry->unregisterAll();

        self::assertTrue($this->registry->isEmpty());
    }

    public function testReRegisterSameKeyReplacesServer(): void
    {
        $server1 = $this->createMock(Server::class);
        $server1->expects(self::once())->method('stop');
        $server2 = $this->createMock(Server::class);

        $this->registry->register('TestServer', $server1);
        $this->registry->register('TestServer', $server2);

        self::assertSame($server2, $this->registry->get('TestServer'));
    }

    public function testDispatchRoutesToCorrectServer(): void
    {
        $petServer = (new Server())
            ->withSchema(__DIR__ . '/../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Buddy']]);

        $bookServer = (new Server())
            ->withSchema(__DIR__ . '/../Fixtures/openapi/bookstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listBooks', 200, [['id' => 1, 'title' => 'PHP in Action']]);

        $this->registry->register('PetServer', $petServer);
        $this->registry->register('BookServer', $bookServer);

        $petRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $petResponse = $this->registry->dispatch($petRequest);
        self::assertSame(200, $petResponse->getStatusCode());
        self::assertStringContainsString('Buddy', $petResponse->getBody());

        $bookRequest = new VcrRequest('GET', 'https://api.bookstore.example.com/books', []);
        $bookResponse = $this->registry->dispatch($bookRequest);
        self::assertSame(200, $bookResponse->getStatusCode());
        self::assertStringContainsString('PHP in Action', $bookResponse->getBody());
    }

    public function testDispatchReturns502ForUnknownUrl(): void
    {
        $server = (new Server())
            ->withSchema(__DIR__ . '/../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $this->registry->register('PetServer', $server);

        $request = new VcrRequest('GET', 'https://unknown.example.com/foo', []);
        $response = $this->registry->dispatch($request);

        self::assertSame(502, $response->getStatusCode());
    }
}
