<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Handler;
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

/**
 * @covers \OasFake\InterceptorRouter
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\CassetteSession
 * @uses \OasFake\Converter
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\Interceptor
 * @uses \OasFake\MiddlewarePipeline
 * @uses \OasFake\Mode
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\OperationPathResolver
 * @uses \OasFake\OperationRequest
 * @uses \OasFake\OperationRequestResolver
 * @uses \OasFake\OperationResponder
 * @uses \OasFake\OperationResponseResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\Schema
 * @uses \OasFake\SchemaRequestHandler
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\VcrResponseFactory
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\JsonHandlerBody
 */
#[CoversClass(InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
final class InterceptorRouterTest extends TestCase
{
    public function testAddRegistersRoutesForDispatch(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, Mode::fromString(Mode::FAKE));

        self::assertNotNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testRemoveDeletesOnlyOwnedRoutes(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, Mode::fromString(Mode::FAKE));
        $router->remove('pet');

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testClearDeletesAllRoutes(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $interceptor = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], new HandlerMap(), false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('pet', $schema->serverUrls(), $interceptor, Mode::fromString(Mode::FAKE));
        $router->clear();

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', [])));
    }

    public function testDispatchReturnsNullForUnknownUrl(): void
    {
        $router = new InterceptorRouter(new ServerUrlMatcher());

        self::assertNull($router->dispatch(new VcrRequest('GET', 'https://unknown.example.com/pets', [])));
    }

    public function testRemoveRestoresThePreviousRouteForTheSameUrl(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $firstHandlers = new HandlerMap();
        $firstHandlers->forOperation('listPets', Handler::response(200, [['name' => 'First']]));
        $secondHandlers = new HandlerMap();
        $secondHandlers->forOperation('listPets', Handler::response(200, [['name' => 'Second']]));
        $first = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], $firstHandlers, false, false);
        $second = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], $secondHandlers, false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('first', $schema->serverUrls(), $first, Mode::fromString(Mode::FAKE));
        $router->add('second', $schema->serverUrls(), $second, Mode::fromString(Mode::FAKE));
        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        self::assertStringContainsString('Second', $router->dispatch($request)?->getBody() ?? '');
        $router->remove('second');
        self::assertStringContainsString('First', $router->dispatch($request)?->getBody() ?? '');
    }

    public function testDispatchChoosesTheMostSpecificMatchingUrl(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml');
        $rootHandlers = new HandlerMap();
        $rootHandlers->forOperation('listPets', Handler::response(200, [['name' => 'Root']]));
        $versionedHandlers = new HandlerMap();
        $versionedHandlers->forOperation('listPets', Handler::response(200, [['name' => 'Versioned']]));
        $root = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], $rootHandlers, false, false);
        $versioned = new Interceptor(Mode::FAKE, sys_get_temp_dir(), $schema, new Validator($schema), [], $versionedHandlers, false, false);
        $router = new InterceptorRouter(new ServerUrlMatcher());
        $router->add('root', ['https://api.versioned.example.com'], $root, Mode::fromString(Mode::FAKE));
        $router->add('versioned', ['https://api.versioned.example.com/v1'], $versioned, Mode::fromString(Mode::FAKE));

        $response = $router->dispatch(new VcrRequest('GET', 'https://api.versioned.example.com/v1/pets', []));

        self::assertNotNull($response);
        self::assertStringContainsString('Versioned', $response->getBody());
    }
}
