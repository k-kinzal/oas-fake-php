<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\HandlerMap;
use OasFake\Interceptor;
use OasFake\InterceptorFactory;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\ServerOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InterceptorFactory::class)]
final class InterceptorFactoryTest extends TestCase
{
    public function testCreateReturnsInterceptor(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $options = new ServerOptions(
            schema: $schema,
            mode: Mode::FAKE,
            cassettePath: sys_get_temp_dir() . '/oas-fake-test-cassettes',
            validateRequests: true,
            validateResponses: true,
            fakerOptions: [],
            middleware: [],
        );

        $interceptor = (new InterceptorFactory())->create($options, new HandlerMap());

        self::assertInstanceOf(Interceptor::class, $interceptor);
        self::assertFalse($interceptor->isRunning());
    }
}
