<?php

declare(strict_types=1);

namespace OasFake\Examples\CustomResponses;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use OasFake\OasFake;
use OasFake\Handler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CustomResponsesTest extends TestCase
{
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    private function createClient(): Client
    {
        return new Client([
            'base_uri' => 'https://api.shop.example.com',
            'http_errors' => false,
        ]);
    }

    public function testStubResponse(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Widget', 'price' => 9.99],
            ['id' => 2, 'name' => 'Gadget', 'price' => 19.99],
        ];

        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withResponse('listProducts', 200, $products));

        $client = $this->createClient();
        $response = $client->get('/products');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $body);
        self::assertSame('Widget', $body[0]['name']);
    }

    public function testStubCallback(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withCallback('createProduct', static function (ServerRequestInterface $request, ?ResponseInterface $default): ResponseInterface {
                $body = json_decode((string) $request->getBody(), true);

                return new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
                    'id' => 100,
                    'name' => $body['name'],
                    'price' => $body['price'],
                ]));
            }));

        $client = $this->createClient();
        $response = $client->post('/products', [
            'json' => ['name' => 'New Product', 'price' => 29.99],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('New Product', $body['name']);
        self::assertSame(29.99, $body['price']);
    }

    public function testStubStatus(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withHandler('getProduct', Handler::status(200)));

        $client = $this->createClient();
        $response = $client->get('/products/1');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testWithResponseByOperationId(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withResponse('getProduct', 200, ['id' => 42, 'name' => 'Specific', 'price' => 5.0]));

        $client = $this->createClient();
        $response = $client->get('/products/42');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Specific', $body['name']);
    }

    public function testWithCallbackByOperationId(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withCallback('getProduct', static function (ServerRequestInterface $request): ResponseInterface {
                return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'id' => 1,
                    'name' => 'Callback Product',
                    'price' => 15.0,
                ]));
            }));

        $client = $this->createClient();
        $response = $client->get('/products/1');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Callback Product', $body['name']);
    }

    public function testWithPathResponse(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withPathResponse('/products', 'GET', 200, [
                ['id' => 1, 'name' => 'Path Product', 'price' => 10.0],
            ]));

        $client = $this->createClient();
        $response = $client->get('/products');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Path Product', $body[0]['name']);
    }

    public function testWithPathCallback(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withPathCallback('/products', 'GET', static function (): ResponseInterface {
                return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    ['id' => 1, 'name' => 'Path Callback Product', 'price' => 20.0],
                ]));
            }));

        $client = $this->createClient();
        $response = $client->get('/products');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Path Callback Product', $body[0]['name']);
    }

    public function testWithStubDirectly(): void
    {
        $stub = Handler::response(200, ['id' => 1, 'name' => 'Stub Direct', 'price' => 7.5]);

        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withHandler('getProduct', $stub));

        $client = $this->createClient();
        $response = $client->get('/products/1');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Stub Direct', $body['name']);
    }

    public function testOperationIdPriorityOverPathMethod(): void
    {
        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withResponse('listProducts', 200, [['id' => 1, 'name' => 'ByOperationId', 'price' => 1.0]])
            ->withPathResponse('/products', 'GET', 200, [['id' => 2, 'name' => 'ByPath', 'price' => 2.0]]));

        $client = $this->createClient();
        $response = $client->get('/products');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('ByOperationId', $body[0]['name']);
    }

    public function testFakerFallbackWhenNoStub(): void
    {
        OasFake::start(ECommerceServer::class);

        $client = $this->createClient();
        $response = $client->get('/products');

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
    }

    public function testNestedOrderResponse(): void
    {
        $order = [
            'id' => 1,
            'items' => [
                ['productId' => 10, 'quantity' => 2, 'price' => 19.99],
                ['productId' => 20, 'quantity' => 1, 'price' => 9.99],
            ],
            'total' => 49.97,
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'country' => 'US',
                'zip' => '62704',
            ],
        ];

        OasFake::start(ECommerceServer::class, static fn (ECommerceServer $s) => $s
            ->withResponse('createOrder', 201, $order));

        $client = $this->createClient();
        $response = $client->post('/orders', [
            'json' => [
                'items' => [
                    ['productId' => 10, 'quantity' => 2],
                    ['productId' => 20, 'quantity' => 1],
                ],
            ],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(201, $response->getStatusCode());
        self::assertCount(2, $body['items']);
        self::assertSame(49.97, $body['total']);
        self::assertSame('Springfield', $body['shippingAddress']['city']);
    }
}
