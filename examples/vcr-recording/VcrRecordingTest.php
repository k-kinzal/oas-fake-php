<?php

declare(strict_types=1);

namespace OasFake\Examples\VcrRecording;

use GuzzleHttp\Client;
use OasFake\Mode;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class VcrRecordingTest extends TestCase
{
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testFakeModeReturnsGeneratedResponse(): void
    {
        OasFake::start(WeatherServer::class, static fn (WeatherServer $s) => $s
            ->withMode(Mode::FAKE));

        $client = new Client(['base_uri' => 'https://api.weather.example.com']);
        $response = $client->get('/forecast', [
            'query' => ['city' => 'Tokyo', 'days' => 3],
        ]);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('city', $body);
        self::assertArrayHasKey('days', $body);
    }

    public function testFakeModeCurrentWeatherWithQueryParams(): void
    {
        OasFake::start(WeatherServer::class, static fn (WeatherServer $s) => $s
            ->withMode(Mode::FAKE));

        $client = new Client(['base_uri' => 'https://api.weather.example.com']);
        $response = $client->get('/current', [
            'query' => ['city' => 'London'],
        ]);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('city', $body);
        self::assertArrayHasKey('temperature', $body);
        self::assertArrayHasKey('condition', $body);
    }

    public function testCassettePathConfiguration(): void
    {
        $server = OasFake::start(WeatherServer::class, static fn (WeatherServer $s) => $s
            ->withCassettePath(__DIR__ . '/cassettes')
            ->withMode(Mode::FAKE));

        self::assertInstanceOf(WeatherServer::class, $server);
        self::assertTrue($server->isRunning());
    }

    public function testModeSwitching(): void
    {
        $server = OasFake::start(WeatherServer::class, static fn (WeatherServer $s) => $s
            ->withMode(Mode::FAKE));

        $client = new Client(['base_uri' => 'https://api.weather.example.com']);
        $response = $client->get('/forecast', [
            'query' => ['city' => 'Paris', 'days' => 5],
        ]);
        self::assertSame(200, $response->getStatusCode());

        OasFake::stop();
        self::assertFalse($server->isRunning());

        $server = OasFake::start(WeatherServer::class, static fn (WeatherServer $s) => $s
            ->withMode(Mode::FAKE));

        self::assertTrue($server->isRunning());
    }

    public function testModeEnumValues(): void
    {
        self::assertSame('fake', Mode::FAKE);
        self::assertSame('record', Mode::RECORD);
        self::assertSame('replay', Mode::REPLAY);
    }

    public function testModeFromString(): void
    {
        self::assertSame(Mode::FAKE, Mode::fromString('fake'));
        self::assertSame(Mode::RECORD, Mode::fromString('record'));
        self::assertSame(Mode::REPLAY, Mode::fromString('replay'));
        self::assertSame(Mode::FAKE, Mode::fromString('FAKE'));
    }
}
