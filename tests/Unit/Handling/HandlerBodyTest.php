<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\HandlerBody;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class HandlerBodyTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     */
    public function testEncodeDefinesObservableBodyContract(): void
    {
        $body = new class () implements HandlerBody {
            /**
             * Return a deterministic encoded fixture.
             */
            public function encode(): string
            {
                return 'encoded';
            }
        };

        self::assertSame('encoded', $body->encode());
    }
}
