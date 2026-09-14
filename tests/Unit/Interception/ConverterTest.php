<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

/**
 * @uses \OasFake\LosslessVcrResponse
 *
 * @covers \OasFake\Converter
 *
 * @uses \OasFake\VcrResponseFactory
 */
#[CoversClass(Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\LosslessVcrResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
final class ConverterTest extends TestCase
{
    public function testRequestToPsr7ConvertsUrlMethodHeadersAndBody(): void
    {
        $vcrRequest = new VcrRequest(
            'POST',
            'http://example.com/pets',
            ['Content-Type' => 'application/json'],
        );
        $vcrRequest->setBody('{"name":"Rex"}');

        $psr7 = (new Converter())->requestToPsr7($vcrRequest);

        self::assertSame('POST', $psr7->getMethod());
        self::assertSame('http://example.com/pets', (string) $psr7->getUri());
        self::assertSame('application/json', $psr7->getHeaderLine('Content-Type'));
        self::assertSame('{"name":"Rex"}', (string) $psr7->getBody());
    }

    public function testRequestToPsr7ParsesQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets?limit=10&offset=20',
        );

        $psr7 = (new Converter())->requestToPsr7($vcrRequest);

        self::assertSame(['limit' => '10', 'offset' => '20'], $psr7->getQueryParams());
    }

    public function testRequestToPsr7PreservesRepeatedQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets?tag=friendly&tag=small&name=Buddy+Jr&include',
        );

        $psr7 = (new Converter())->requestToPsr7($vcrRequest);

        self::assertSame(
            [
                'tag' => ['friendly', 'small'],
                'name' => 'Buddy Jr',
                'include' => null,
            ],
            $psr7->getQueryParams(),
        );
    }

    public function testRequestToPsr7HandlesNoQueryParams(): void
    {
        $vcrRequest = new VcrRequest(
            'GET',
            'http://example.com/pets',
        );

        $psr7 = (new Converter())->requestToPsr7($vcrRequest);

        self::assertSame([], $psr7->getQueryParams());
    }

    public function testPsr7ToVcrResponseConvertsStatusHeadersAndBody(): void
    {
        $psrResponse = new Response(
            201,
            ['Content-Type' => 'application/json', 'X-Request-Id' => 'abc123'],
            '{"id":1}',
        );

        $vcrResponse = (new Converter())->psr7ToVcrResponse($psrResponse);

        self::assertSame(201, $vcrResponse->getStatusCode());
        self::assertSame('application/json', $vcrResponse->getHeader('Content-Type'));
        self::assertSame('abc123', $vcrResponse->getHeader('X-Request-Id'));
        self::assertSame('{"id":1}', $vcrResponse->getBody());
    }

    public function testPsr7ToVcrResponsePreservesMultipleHeaderValues(): void
    {
        $psrResponse = new Response(
            200,
            [
                'Set-Cookie' => [
                    'session=abc; Path=/; HttpOnly',
                    'theme=dark; Path=/',
                ],
            ],
            'ok',
        );

        $vcrResponse = (new Converter())->psr7ToVcrResponse($psrResponse);
        $restored = (new Converter())->vcrResponseToPsr7($vcrResponse);

        self::assertSame(
            ['session=abc; Path=/; HttpOnly', 'theme=dark; Path=/'],
            $vcrResponse->getHeaders()['Set-Cookie'],
        );
        self::assertSame(
            ['session=abc; Path=/; HttpOnly', 'theme=dark; Path=/'],
            $restored->getHeader('Set-Cookie'),
        );
    }

    public function testVcrResponseToPsr7ConvertsStatusHeadersAndBody(): void
    {
        $vcrResponse = VcrResponse::fromArray([
            'status' => ['code' => 200, 'message' => 'OK'],
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"ok":true}',
        ]);

        $psrResponse = (new Converter())->vcrResponseToPsr7($vcrResponse);

        self::assertSame(200, $psrResponse->getStatusCode());
        self::assertSame('application/json', $psrResponse->getHeaderLine('Content-Type'));
        self::assertSame('{"ok":true}', (string) $psrResponse->getBody());
    }

    public function testRoundTripPsr7ToVcrAndBackPreservesData(): void
    {
        $original = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"round":"trip"}',
        );

        $vcrResponse = (new Converter())->psr7ToVcrResponse($original);
        $restored = (new Converter())->vcrResponseToPsr7($vcrResponse);

        self::assertSame($original->getStatusCode(), $restored->getStatusCode());
        self::assertSame((string) $original->getBody(), (string) $restored->getBody());
        self::assertSame(
            $original->getHeaderLine('Content-Type'),
            $restored->getHeaderLine('Content-Type'),
        );
    }

    /**
     * @group pbt
     */
    #[\PHPUnit\Framework\Attributes\Group('pbt')]
    public function testPsr7ToVcrResponsePreservesGeneratedMessages(): void
    {
        $configuredSeed = getenv('ERIS_SEED');
        $seed = $configuredSeed !== false && $configuredSeed !== '' ? (int) $configuredSeed : random_int(0, PHP_INT_MAX);
        $random = new \Eris\Random\RandomRange(new \Eris\Random\MersenneTwister());
        $random->seed($seed);
        $property = new \Eris\Quantifier\ForAll(
            [
                \Eris\Generators::choose(100, 599),
                \Eris\Generators::seq(\Eris\Generators::choose(0, 255)),
                \Eris\Generators::seq(\Eris\Generators::choose(0, 1000000)),
            ],
            500,
            new \Eris\Shrinker\ShrinkerFactory(['timeLimit' => null]),
            'multiple',
            $random,
        );

        $property->then(
            /**
             * @param list<int> $bytes
             * @param list<int> $values
             */
            static function (int $status, array $bytes, array $values) use ($seed): void {
                $body = pack('C*', ...$bytes);
                $replay = 'ERIS_SEED=' . $seed . ' composer test:pbt';
                $headerValues = [];
                foreach ($values as $value) {
                    self::assertIsInt($value, $replay);
                    $headerValues[] = (string) $value;
                }
                $headers = ['X-Values' => $headerValues];
                $original = new Response($status, $headers, $body);
                $converter = new Converter();
                $recorded = $converter->psr7ToVcrResponse($original);
                $restored = $converter->vcrResponseToPsr7($recorded);

                self::assertSame($status, $recorded->getStatusCode(), $replay);
                self::assertSame($body, $recorded->getBody(), $replay);
                self::assertSame($status, $restored->getStatusCode(), $replay);
                self::assertSame($headers['X-Values'], $restored->getHeader('X-Values'), $replay);
                self::assertSame($body, (string) $restored->getBody(), $replay);
                self::assertSame($body, (string) $original->getBody(), $replay);
            },
        );
    }
}
