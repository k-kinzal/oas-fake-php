<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\HandlerMap;
use OasFake\Interceptor;
use OasFake\InterceptorRouter;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\ServerUrlMatcher;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;

#[CoversClass(InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
final class InterceptorRouterTest extends TestCase
{
    public function testAddRegistersRoutesForDispatch(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, new Mode(Mode::FAKE));

        self::assertNotNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testRemoveDeletesOnlyOwnedRoutes(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, new Mode(Mode::FAKE));
        $router->remove('pet');

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testClearDeletesAllRoutes(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, new Mode(Mode::FAKE));
        $router->clear();

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testDispatchReturnsNullForUnknownUrl(): void
    {
        $router = new InterceptorRouter(new ServerUrlMatcher());

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://unknown.example.com/pets', [])));
    }
}
