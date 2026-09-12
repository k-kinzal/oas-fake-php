<?php

declare(strict_types=1);

namespace OasFake\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use GuzzleHttp\Psr7\Response;
use OasFake\Converter;
use OasFake\VcrResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;

/**
 * @uses \OasFake\LosslessVcrResponse
 *
 * @covers \OasFake\Converter
 * @covers \OasFake\VcrResponseFactory
 *
 * @group pbt
 *
 * @medium
 */
#[CoversClass(Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\LosslessVcrResponse::class)]
#[CoversClass(VcrResponseFactory::class)]
#[Group('pbt')]
#[Medium]
final class ResponseConversionTest extends TestCase
{
    use TestTrait;

    /**
     * Preserve message content across the recording representation without changing its source.
     */
    public function testConversionPreservesStatusRepeatedHeadersAndBinaryBody(): void
    {
        $this->limitTo(500)->forAll(
            Generators::choose(100, 599),
            Generators::seq(Generators::choose(0, 255)),
            Generators::seq(Generators::choose(0, 1000000)),
        )->then(/** @param list<int> $bytes
                 * @param list<int> $values
                 */ static function (int $status, array $bytes, array $values): void {
            $body = pack('C*', ...$bytes);
            $headerValues = [];
            foreach ($values as $value) {
                self::assertIsInt($value);
                $headerValues[] = (string) $value;
            }
            $headers = ['X-Values' => $headerValues];
            $original = new Response($status, $headers, $body);
            $converter = new Converter();
            $recorded = $converter->psr7ToVcrResponse($original);
            $restored = $converter->vcrResponseToPsr7($recorded);

            self::assertSame($status, $recorded->getStatusCode());
            self::assertSame($body, $recorded->getBody());
            self::assertSame($status, $restored->getStatusCode());
            self::assertSame($headers['X-Values'], $restored->getHeader('X-Values'));
            self::assertSame($body, (string) $restored->getBody());
            self::assertSame($body, (string) $original->getBody());
        });
    }
}
