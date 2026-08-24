<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\CassetteNameNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CassetteNameNormalizer::class)]
final class CassetteNameNormalizerTest extends TestCase
{
    public function testNormalizeProducesPortableFallbackAwareNames(): void
    {
        $normalizer = new CassetteNameNormalizer();

        self::assertSame('oasfake-tests-pet-server', $normalizer->normalize('OasFake\\Tests\\Pet Server'));
        self::assertSame('recording', $normalizer->normalize('!!!'));
    }
}
