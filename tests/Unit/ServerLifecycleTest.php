<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Exception\ServerStateException;
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerStateException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Validator::class)]
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

        $this->expectException(ServerStateException::class);
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
