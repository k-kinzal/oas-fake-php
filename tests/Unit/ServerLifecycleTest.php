<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use LogicException;
use OasFake\HandlerMap;
use OasFake\Interceptor;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\ServerLifecycle;
use OasFake\ServerRegistry;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerLifecycle::class)]
final class ServerLifecycleTest extends TestCase
{
    public function testAssertConfigurableAllowsStoppedLifecycle(): void
    {
        (new ServerLifecycle())->assertConfigurable();

        $this->addToAssertionCount(1);
    }

    public function testAssertCanRegisterRejectsAnotherRegistry(): void
    {
        $lifecycle = new ServerLifecycle();
        $lifecycle->register(new ServerRegistry(), 'one');

        $this->expectException(LogicException::class);
        $lifecycle->assertCanRegister(new ServerRegistry(), 'two');
    }

    public function testRegisterAttachesRegistry(): void
    {
        $lifecycle = new ServerLifecycle();
        $registry = new ServerRegistry();
        $lifecycle->register($registry, 'pet');

        self::assertSame($registry, $lifecycle->registration()['registry'] ?? null);
    }

    public function testRegistrationReturnsNullBeforeAttachment(): void
    {
        self::assertNull((new ServerLifecycle())->registration());
    }

    public function testUnregisterDetachesMatchingRegistry(): void
    {
        $lifecycle = new ServerLifecycle();
        $registry = new ServerRegistry();
        $lifecycle->register($registry, 'pet');
        $lifecycle->unregister($registry, 'pet');

        self::assertNull($lifecycle->registration());
    }

    public function testUnregisterIgnoresNonOwningRegistry(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $interceptor->start();
        $lifecycle = new ServerLifecycle();
        $owner = new ServerRegistry();
        $lifecycle->register($owner, 'pet');
        $lifecycle->replaceInterceptor($interceptor);

        $lifecycle->unregister(new ServerRegistry(), 'pet');

        self::assertSame($owner, $lifecycle->registration()['registry'] ?? null);
        self::assertTrue($interceptor->isRunning());
    }

    public function testReplaceInterceptorStoresBuiltInterceptor(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $lifecycle = new ServerLifecycle();
        $lifecycle->replaceInterceptor($interceptor);

        self::assertSame($interceptor, $lifecycle->interceptor());
    }

    public function testInterceptorReturnsNullByDefault(): void
    {
        self::assertNull((new ServerLifecycle())->interceptor());
    }

    public function testIsRunningTracksStartedInterceptor(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $interceptor->start();
        $lifecycle = new ServerLifecycle();
        $lifecycle->replaceInterceptor($interceptor);

        self::assertTrue($lifecycle->isRunning());
    }

    public function testStopInterceptorStopsAndReleasesIt(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $interceptor->start();
        $lifecycle = new ServerLifecycle();
        $lifecycle->replaceInterceptor($interceptor);
        $lifecycle->stopInterceptor();

        self::assertFalse($interceptor->isRunning());
        self::assertNull($lifecycle->interceptor());
    }
}
