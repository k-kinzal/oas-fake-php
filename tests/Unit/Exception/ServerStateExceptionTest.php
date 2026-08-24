<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use OasFake\Exception\ServerStateException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerStateException::class)]
final class ServerStateExceptionTest extends TestCase
{
    public function testConfigurationLockedExplainsAllowedTransition(): void
    {
        self::assertStringContainsString('before start()', ServerStateException::configurationLocked()->getMessage());
    }

    public function testAlreadyRegisteredExplainsOwnershipConflict(): void
    {
        self::assertStringContainsString('another ServerRegistry', ServerStateException::alreadyRegistered()->getMessage());
    }
}
