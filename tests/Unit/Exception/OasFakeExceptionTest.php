<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\OasFakeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OasFakeException::class)]
final class OasFakeExceptionTest extends TestCase
{
    public function testCarriesFailureMessage(): void
    {
        $exception = new OasFakeException('Failure');

        self::assertSame('Failure', $exception->getMessage());
    }
}
