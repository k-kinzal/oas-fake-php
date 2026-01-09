<?php

declare(strict_types=1);

namespace OasFake\Examples\FakerCustomization;

use GuzzleHttp\Client;
use OasFake\OasFake;
use PHPUnit\Framework\TestCase;

final class FakerCustomizationTest extends TestCase
{
    protected function tearDown(): void
    {
        OasFake::stop();
    }

    public function testDefaultFakerGeneratesRequiredFields(): void
    {
        OasFake::start(BlogServer::class);

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles/1');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('title', $body);
        self::assertArrayHasKey('body', $body);
    }

    public function testAlwaysFakeOptionalsGeneratesAllFields(): void
    {
        OasFake::start(BlogServer::class, static fn (BlogServer $s) => $s
            ->withFakerOptions(['alwaysFakeOptionals' => true]));

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles/1');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('tags', $body);
        self::assertArrayHasKey('author', $body);
        self::assertArrayHasKey('comments', $body);

        self::assertArrayHasKey('id', $body['author']);
        self::assertArrayHasKey('name', $body['author']);
    }

    public function testMinMaxItemsControlsArraySize(): void
    {
        OasFake::start(BlogServer::class, static fn (BlogServer $s) => $s
            ->withFakerOptions([
                'alwaysFakeOptionals' => true,
                'minItems' => 2,
                'maxItems' => 3,
            ]));

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($body);
        self::assertGreaterThanOrEqual(2, count($body));
        self::assertLessThanOrEqual(3, count($body));
    }

    public function testNestedObjectGeneration(): void
    {
        OasFake::start(BlogServer::class, static fn (BlogServer $s) => $s
            ->withFakerOptions(['alwaysFakeOptionals' => true]));

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles/1');
        $body = json_decode((string) $response->getBody(), true);

        self::assertIsArray($body['author']);
        self::assertArrayHasKey('id', $body['author']);
        self::assertArrayHasKey('name', $body['author']);

        self::assertIsArray($body['comments']);
        if (count($body['comments']) > 0) {
            $comment = $body['comments'][0];
            self::assertArrayHasKey('id', $comment);
            self::assertArrayHasKey('text', $comment);
        }
    }

    public function testStaticFakerOptions(): void
    {
        OasFake::start(BlogServer::class);

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles/1');

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
    }

    public function testFakerOptionsViaConfigureCallback(): void
    {
        OasFake::start(BlogServer::class, static fn (BlogServer $s) => $s
            ->withFakerOptions(['alwaysFakeOptionals' => true, 'minItems' => 1, 'maxItems' => 2]));

        $client = new Client(['base_uri' => 'https://api.blog.example.com']);
        $response = $client->get('/articles');
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($body);
        self::assertGreaterThanOrEqual(1, count($body));
        self::assertLessThanOrEqual(2, count($body));
    }
}
