<?php

declare(strict_types=1);

namespace OasFake\Examples\Configuration;

use GuzzleHttp\Client;
use OasFake\Mode;
use OasFake\OasFake;
use OasFake\Server;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('OAS_FAKE_MODE');
        putenv('OAS_FAKE_VALIDATE_REQUESTS');
        putenv('OAS_FAKE_VALIDATE_RESPONSES');
        putenv('OAS_FAKE_CASSETTE_PATH');
    }

    protected function tearDown(): void
    {
        OasFake::stop();
        putenv('OAS_FAKE_MODE');
        putenv('OAS_FAKE_VALIDATE_REQUESTS');
        putenv('OAS_FAKE_VALIDATE_RESPONSES');
        putenv('OAS_FAKE_CASSETTE_PATH');
    }

    public function testStartWithConfigureCallback(): void
    {
        OasFake::start(StatusServer::class, static fn (StatusServer $s) => $s
            ->withMode(Mode::FAKE)
            ->withResponseValidation(false));

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());
    }

    public function testStartWithServerInstance(): void
    {
        $server = new StatusServer();
        $result = OasFake::start($server);

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($server, $result);
    }

    public function testStartWithClassStringAndCallback(): void
    {
        $callbackInvoked = false;

        $server = OasFake::start(StatusServer::class, static function (Server $s) use (&$callbackInvoked): Server {
            $callbackInvoked = true;

            return $s->withResponseValidation(false);
        });

        self::assertTrue($callbackInvoked);
        self::assertTrue($server->isRunning());
    }

    public function testEnvVarOverridesMode(): void
    {
        putenv('OAS_FAKE_MODE=fake');

        $server = OasFake::start(StatusServer::class);

        self::assertTrue($server->isRunning());
    }

    public function testEnvVarOverridesValidation(): void
    {
        putenv('OAS_FAKE_VALIDATE_REQUESTS=false');

        OasFake::start(StatusServer::class, static fn (StatusServer $s) => $s
            ->withRequestValidation(true)
            ->withResponseValidation(false));

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());
    }

    public function testFluentApiOverridesStaticProperties(): void
    {
        OasFake::start(StatusServer::class, static fn (StatusServer $s) => $s
            ->withResponseValidation(false)
            ->withResponse('getStatus', 200, ['invalid' => 'data']));

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());
    }

    public function testConfigResolutionOrder(): void
    {
        putenv('OAS_FAKE_MODE=fake');

        $server = OasFake::start(StatusServer::class, static fn (StatusServer $s) => $s
            ->withMode(Mode::FAKE));

        self::assertTrue($server->isRunning());

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());
    }

    public function testFluentChaining(): void
    {
        OasFake::start(StatusServer::class, static fn (StatusServer $s) => $s
            ->withMode(Mode::FAKE)
            ->withCassettePath(__DIR__ . '/cassettes')
            ->withRequestValidation(true)
            ->withResponseValidation(false)
            ->withFakerOptions(['alwaysFakeOptionals' => true]));

        $client = new Client(['base_uri' => 'https://api.status.example.com']);
        $response = $client->get('/status');
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('healthy', $body);
        self::assertArrayHasKey('version', $body);
    }
}
