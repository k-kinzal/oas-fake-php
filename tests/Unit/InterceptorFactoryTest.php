<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\HandlerMap;
use OasFake\InterceptorFactory;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\ServerOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InterceptorFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Interceptor::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(ServerOptions::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
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

        self::assertFalse($interceptor->isRunning());
    }
}
