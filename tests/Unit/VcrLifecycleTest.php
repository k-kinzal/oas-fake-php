<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\VcrLifecycle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request;
use VCR\Response;

#[CoversClass(VcrLifecycle::class)]
final class VcrLifecycleTest extends TestCase
{
    public function testActivateMarksLifecycleActive(): void
    {
        $lifecycle = new VcrLifecycle();

        try {
            $lifecycle->activate(static fn (Request $request): Response => new Response('200', [], 'ok'));

            self::assertTrue($lifecycle->isActive());
        } finally {
            $lifecycle->deactivate();
        }
    }

    public function testDeactivateMarksLifecycleInactive(): void
    {
        $lifecycle = new VcrLifecycle();
        $lifecycle->activate(static fn (Request $request): Response => new Response('200', [], 'ok'));
        $lifecycle->deactivate();

        self::assertFalse($lifecycle->isActive());
    }

    public function testIsActiveIsFalseInitially(): void
    {
        self::assertFalse((new VcrLifecycle())->isActive());
    }
}
