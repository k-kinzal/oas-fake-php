<?php

declare(strict_types=1);

namespace OasFake\Examples\Middleware;

use GuzzleHttp\Client;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        OrderTrackingMiddleware::reset();
        AuthServer::clearStaticMiddleware();
    }

    protected function tearDown(): void
    {
        OasFake::stop();
        OrderTrackingMiddleware::reset();
        AuthServer::clearStaticMiddleware();
    }

    public function testResponseHeaderMiddleware(): void
    {
        $requestId = 'test-request-id-123';

        OasFake::start(AuthServer::class, static fn (AuthServer $s) => $s
            ->withMiddleware(new RequestIdMiddleware($requestId)));

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $response = $client->get('/me');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($requestId, $response->getHeader('X-Request-Id')[0]);
    }

    public function testRequestRecorderMiddleware(): void
    {
        $recorder = new RequestRecorderMiddleware();

        OasFake::start(AuthServer::class, static fn (AuthServer $s) => $s
            ->withMiddleware($recorder));

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $client->get('/me');
        $client->get('/settings');

        $calls = $recorder->getCalls();
        self::assertCount(2, $calls);
        self::assertSame('GET', $calls[0]['method']);
        self::assertSame('/me', $calls[0]['path']);
        self::assertSame('GET', $calls[1]['method']);
        self::assertSame('/settings', $calls[1]['path']);
    }

    public function testFluentMiddlewareRegistration(): void
    {
        $requestId = 'fluent-id';
        $recorder = new RequestRecorderMiddleware();

        OasFake::start(AuthServer::class, static fn (AuthServer $s) => $s
            ->withMiddleware(new RequestIdMiddleware($requestId))
            ->withMiddleware($recorder));

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $response = $client->get('/me');

        self::assertSame($requestId, $response->getHeader('X-Request-Id')[0]);
        self::assertCount(1, $recorder->getCalls());
    }

    public function testStaticMiddlewareDeclaration(): void
    {
        $recorder = new RequestRecorderMiddleware();
        AuthServer::setStaticMiddleware($recorder);

        OasFake::start(AuthServer::class);

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $client->get('/me');

        self::assertCount(1, $recorder->getCalls());
        self::assertSame('/me', $recorder->getCalls()[0]['path']);
    }

    public function testMiddlewareExecutionOrder(): void
    {
        OasFake::start(AuthServer::class, static fn (AuthServer $s) => $s
            ->withMiddleware(new OrderTrackingMiddleware(1))
            ->withMiddleware(new OrderTrackingMiddleware(2))
            ->withMiddleware(new OrderTrackingMiddleware(3)));

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $client->get('/me');

        self::assertSame([1, 2, 3], OrderTrackingMiddleware::getExecutionOrder());
    }

    public function testMultipleMiddlewareCombined(): void
    {
        $requestId = 'combined-id';
        $recorder = new RequestRecorderMiddleware();

        OasFake::start(AuthServer::class, static fn (AuthServer $s) => $s
            ->withMiddleware(new RequestIdMiddleware($requestId))
            ->withMiddleware($recorder)
            ->withMiddleware(new OrderTrackingMiddleware(1)));

        $client = new Client([
            'base_uri' => 'https://api.auth.example.com',
            'headers' => ['Authorization' => 'Bearer test-token'],
        ]);
        $response = $client->get('/settings');

        self::assertSame($requestId, $response->getHeader('X-Request-Id')[0]);
        self::assertCount(1, $recorder->getCalls());
        self::assertSame([1], OrderTrackingMiddleware::getExecutionOrder());
    }
}
