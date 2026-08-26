<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use Closure;
use OasFake\Exception\ValidationException;
use OasFake\Handler;
use OasFake\Mode;
use OasFake\Server;
use OasFake\ServerRegistry;
use OasFake\Testing\CassetteNameTestServer;
use OasFake\Testing\DeclarativeTestServer;
use OasFake\Testing\InvalidSignatureOperationIdServer;
use OasFake\Testing\OperationIdTestServer;
use OasFake\Testing\Petstore;
use OasFake\Testing\RouteTestServer;
use OasFake\Testing\SchemaAwareOperationServer;
use OasFake\Testing\SchemaAwareRouteServer;
use OasFake\Testing\TemporaryDirectory;
use OasFake\Testing\UnknownRouteDeclarativeServer;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use VCR\Request as VcrRequest;

/**
 * @covers \OasFake\Server
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\RequestPathMatcher
 * @uses \OasFake\CassetteNameNormalizer
 * @uses \OasFake\CassetteSession
 * @uses \OasFake\Converter
 * @uses \OasFake\DeclarativeHandlerInspector
 * @uses \OasFake\DeclarativeHandlerRegistrar
 * @uses \OasFake\EnvironmentResolver
 * @uses \OasFake\Exception\ValidationException
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\Interceptor
 * @uses \OasFake\InterceptorFactory
 * @uses \OasFake\InterceptorRouter
 * @uses \OasFake\MiddlewarePipeline
 * @uses \OasFake\MiddlewareRequestHandler
 * @uses \OasFake\Mode
 * @uses \OasFake\OasFake
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
 * @uses \OasFake\Route
 * @uses \OasFake\Schema
 * @uses \OasFake\SchemaRequestHandler
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 * @uses \OasFake\ServerOptions
 * @uses \OasFake\ServerRuntime
 * @uses \OasFake\ServerRegistry
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\VcrLifecycle
 * @uses \OasFake\VcrResponseFactory
 * @uses \OasFake\HandlerTypeMatcher
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\RequestPathMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteNameNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\EnvironmentResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ValidationException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewareRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OasFake::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerOptions::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerTypeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class ServerTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        putenv('OAS_FAKE_MODE');
        putenv('OAS_FAKE_CASSETTE_PATH');
        putenv('OAS_FAKE_CASSETTE_NAME');
        putenv('OAS_FAKE_VALIDATE_REQUESTS');
        putenv('OAS_FAKE_VALIDATE_RESPONSES');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('OAS_FAKE_MODE');
        putenv('OAS_FAKE_CASSETTE_PATH');
        putenv('OAS_FAKE_CASSETTE_NAME');
        putenv('OAS_FAKE_VALIDATE_REQUESTS');
        putenv('OAS_FAKE_VALIDATE_RESPONSES');
    }

    public function testWithSchemaReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withSchema(Petstore::path());

        self::assertSame($server, $result);
    }

    public function testWithModeReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withMode(Mode::RECORD);

        self::assertSame($server, $result);
    }

    public function testWithModeNormalizesFluentMode(): void
    {
        $server = new Server();

        $server->withMode(' Record ');

        self::assertSame(Mode::RECORD, $server->resolveMode()->value());
    }

    public function testWithCassettePathReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withCassettePath('/tmp/cassettes');

        self::assertSame($server, $result);
    }

    public function testWithCassetteNameReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withCassetteName('petstore');

        self::assertSame($server, $result);
    }

    public function testWithRequestValidationReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withRequestValidation(false);

        self::assertSame($server, $result);
    }

    public function testWithResponseValidationReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withResponseValidation(false);

        self::assertSame($server, $result);
    }

    public function testWithFakerOptionsReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withFakerOptions(['alwaysFakeOptionals' => true]);

        self::assertSame($server, $result);
    }

    public function testWithMiddlewareReturnsStatic(): void
    {
        $server = new Server();
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        $result = $server->withMiddleware($middleware);

        self::assertSame($server, $result);
    }

    public function testWithResponseRegistersStub(): void
    {
        $server = new Server();
        $result = $server->withResponse('listPets', 200, [['id' => 1, 'name' => 'Fido']]);

        self::assertSame($server, $result);
    }

    public function testWithCallbackRegistersStub(): void
    {
        $server = new Server();
        $result = $server->withCallback('listPets', static function (ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface {
            return $response ?? new \GuzzleHttp\Psr7\Response(200);
        });

        self::assertSame($server, $result);
    }

    public function testWithHandlerRegistersHandler(): void
    {
        $server = new Server();
        $handler = Handler::status(204);
        $result = $server->withHandler('deletePet', $handler);

        self::assertSame($server, $result);
    }

    public function testWithPathResponseReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withPathResponse('/pets', 'GET', 200, []);

        self::assertSame($server, $result);
    }

    public function testWithPathCallbackReturnsStatic(): void
    {
        $server = new Server();
        $result = $server->withPathCallback('/pets', 'GET', static function (): ResponseInterface {
            return new \GuzzleHttp\Psr7\Response(200);
        });

        self::assertSame($server, $result);
    }

    public function testIsRunningReturnsFalseByDefault(): void
    {
        $server = new Server();

        self::assertFalse($server->isRunning());
    }

    public function testStopWhenNotRunningDoesNothing(): void
    {
        $server = new Server();
        $server->stop();

        self::assertFalse($server->isRunning());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testStartCanBeCalledTwiceSafely(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withCassettePath(sys_get_temp_dir() . '/oas-fake-test-cassettes')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->start();
            $server->start();

            self::assertTrue($server->isRunning());
        } finally {
            $server->stop();
        }

        self::assertFalse($server->isRunning());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testBuildInterceptorStartsInterceptor(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();

            self::assertTrue($server->isRunning());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testBuildInterceptorUsesServerSpecificCassetteName(): void
    {
        $directory = new TemporaryDirectory('oas-fake-server-cassettes');
        $cassettePath = $directory->path();

        $server = (new CassetteNameTestServer())
            ->withMode(Mode::RECORD)
            ->withCassettePath($cassettePath);

        try {
            $server->buildInterceptor();

            self::assertFileExists($cassettePath . '/oasfake-testing-cassettenametestserver');
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testBuildInterceptorUsesConfiguredCassetteName(): void
    {
        $directory = new TemporaryDirectory('oas-fake-server-cassettes');
        $cassettePath = $directory->path();

        $server = (new CassetteNameTestServer())
            ->withMode(Mode::RECORD)
            ->withCassettePath($cassettePath)
            ->withCassetteName('Custom Cassette');

        try {
            $server->buildInterceptor();

            self::assertFileExists($cassettePath . '/custom-cassette');
        } finally {
            $server->stop();
        }
    }

    /**
     * @return iterable<string, array{Closure(Server): Server}>
     */
    public static function providerConfigurationMutations(): iterable
    {
        yield 'schema' => [static fn (Server $server): Server => $server->withSchema(__DIR__ . '/../../Fixtures/openapi/bookstore.yaml')];
        yield 'mode' => [static fn (Server $server): Server => $server->withMode(Mode::RECORD)];
        yield 'cassette path' => [static fn (Server $server): Server => $server->withCassettePath('/tmp/other-cassettes')];
        yield 'cassette name' => [static fn (Server $server): Server => $server->withCassetteName('other')];
        yield 'request validation' => [static fn (Server $server): Server => $server->withRequestValidation(true)];
        yield 'response validation' => [static fn (Server $server): Server => $server->withResponseValidation(true)];
        yield 'faker options' => [static fn (Server $server): Server => $server->withFakerOptions(['alwaysFakeOptionals' => true])];
        yield 'middleware' => [static function (Server $server): Server {
            $middleware = new class () implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    return $handler->handle($request);
                }
            };

            return $server->withMiddleware($middleware);
        }];
        yield 'handler' => [static fn (Server $server): Server => $server->withHandler('deletePet', Handler::status(204))];
        yield 'response' => [static fn (Server $server): Server => $server->withResponse('listPets', 200, [])];
        yield 'callback' => [static fn (Server $server): Server => $server->withCallback('listPets', static fn (): ResponseInterface => new \GuzzleHttp\Psr7\Response(200))];
        yield 'path response' => [static fn (Server $server): Server => $server->withPathResponse('/pets', 'GET', 200, [])];
        yield 'path callback' => [static fn (Server $server): Server => $server->withPathCallback('/pets', 'GET', static fn (): ResponseInterface => new \GuzzleHttp\Psr7\Response(200))];
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testInterceptorReturnsActiveInterceptor(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            self::assertNull($server->interceptor());

            $server->buildInterceptor();

            self::assertNotNull($server->interceptor());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testSchemaReturnsResolvedSchema(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        self::assertSame(['https://api.petstore.example.com'], $server->schema()->serverUrls());
    }

    public function testFakerOptionsReturnsConfiguredOptions(): void
    {
        $server = (new Server())->withFakerOptions(['alwaysFakeOptionals' => true]);

        self::assertSame(['alwaysFakeOptionals' => true], $server->fakerOptions());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testServerUrlsReturnsSchemaUrls(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        self::assertSame(['https://api.petstore.example.com'], $server->serverUrls());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregisterFromRegistryStopsInterceptor(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);
        $registry = new ServerRegistry();

        $server->registerInRegistry($registry, 'PetServer');
        $server->buildInterceptor();

        self::assertTrue($server->isRunning());

        $server->unregisterFromRegistry($registry, 'PetServer');

        self::assertFalse($server->isRunning());
    }

    public function testRegisterInRegistryAcceptsOwner(): void
    {
        $server = new Server();
        $registry = new ServerRegistry();

        $server->registerInRegistry($registry, 'PetServer');
        $server->unregisterFromRegistry($registry, 'PetServer');

        $this->addToAssertionCount(1);
    }

    public function testDeclarativeSubclassConfiguresStaticProperties(): void
    {
        $server = new DeclarativeTestServer();
        $result = $server->withMode(Mode::RECORD);
        self::assertSame($server, $result);
    }

    public function testEnvVarOverridesFluentMode(): void
    {
        putenv('OAS_FAKE_MODE=record');

        $server = new Server();
        $server->withSchema(Petstore::path())->withMode(Mode::FAKE);
        self::assertSame('record', getenv('OAS_FAKE_MODE'));
    }

    public function testResolveModeUsesEnvironmentVariable(): void
    {
        putenv('OAS_FAKE_MODE=record');

        try {
            $server = (new Server())->withMode(Mode::FAKE);

            self::assertSame(Mode::RECORD, $server->resolveMode()->value());
        } finally {
            putenv('OAS_FAKE_MODE');
        }
    }

    public function testSubclassMethodsAreAutoRegisteredAsStubs(): void
    {
        $server = new OperationIdTestServer();
        $result = $server->withSchema(Petstore::path());
        self::assertSame($server, $result);
    }

    public function testRouteAttributeMethodsAreRegisteredByPathMethod(): void
    {
        $server = new RouteTestServer();

        $result = $server->withSchema(Petstore::path());
        self::assertSame($server, $result);
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testSchemaAwareOperationIdMethodHandlesMatchingOperation(): void
    {
        $server = (new SchemaAwareOperationServer())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));

            self::assertNotNull($response);
            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString('Declarative operation', $response->getBody());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testSchemaAwareRouteMethodHandlesMatchingPath(): void
    {
        $server = (new SchemaAwareRouteServer())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('DELETE', 'https://api.petstore.example.com/pets/1', []));

            self::assertNotNull($response);
            self::assertSame(204, $response->getStatusCode());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testSchemaAwareRouteMethodSkipsPathOutsideSchema(): void
    {
        $server = (new UnknownRouteDeclarativeServer())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/unknown', []));

            self::assertNotNull($response);
            self::assertSame(500, $response->getStatusCode());
            self::assertStringNotContainsString('Unknown declarative route', $response->getBody());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testPublicMethodsWithoutHandlerSignatureAreNotAutoRegistered(): void
    {
        $server = (new InvalidSignatureOperationIdServer())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $interceptor = $server->interceptor();

            self::assertNotNull($interceptor);

            $response = $interceptor->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));

            self::assertSame(200, $response->getStatusCode());
            self::assertIsArray(json_decode($response->getBody(), true));
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testChangingSchemaInvalidatesTheResolvedSchema(): void
    {
        $server = (new Server())->withSchema(Petstore::path());
        self::assertSame(['https://api.petstore.example.com'], $server->schema()->serverUrls());

        $server->withSchema(__DIR__ . '/../../Fixtures/openapi/bookstore.yaml');

        self::assertSame(['https://api.bookstore.example.com'], $server->schema()->serverUrls());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testRequestValidationIsEnabledWhenCalledWithoutAnArgument(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation()
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $this->expectException(ValidationException::class);
            $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/unknown', []));
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testResponseValidationIsEnabledWhenCalledWithoutAnArgument(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation()
            ->withResponse('listPets', 200, ['not' => 'a list']);

        try {
            $server->buildInterceptor();
            $this->expectException(ValidationException::class);
            $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testHandlerAndMiddlewareConfigurationAffectResponses(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request)->withHeader('X-Middleware', 'applied');
            }
        };
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withHandler('listPets', Handler::response(202, [['id' => 1, 'name' => 'configured']]))
            ->withMiddleware($middleware);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));
            self::assertNotNull($response);
            self::assertSame(202, $response->getStatusCode());
            self::assertSame('applied', $response->getHeaders()['X-Middleware']);
            self::assertStringContainsString('configured', $response->getBody());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testPathResponseConfigurationAffectsResponses(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withPathResponse('/pets', 'GET', 206, [['id' => 1, 'name' => 'path response']]);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));
            self::assertNotNull($response);
            self::assertSame(206, $response->getStatusCode());
            self::assertStringContainsString('path response', $response->getBody());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testPathCallbackConfigurationAffectsResponses(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withPathCallback('/pets', 'GET', static fn (): ResponseInterface => new \GuzzleHttp\Psr7\Response(207, [], 'callback'));

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));
            self::assertNotNull($response);
            self::assertSame(207, $response->getStatusCode());
            self::assertSame('callback', $response->getBody());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testStopReleasesADirectlyBuiltInterceptor(): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);
        $server->buildInterceptor();
        self::assertTrue($server->isRunning());

        $server->stop();

        self::assertFalse($server->isRunning());
        self::assertNull($server->interceptor());
    }

    public function testFluentChaining(): void
    {
        $server = new Server();

        $result = $server
            ->withSchema(Petstore::path())
            ->withMode(Mode::FAKE)
            ->withCassettePath('/tmp/cassettes')
            ->withRequestValidation(true)
            ->withResponseValidation(true)
            ->withFakerOptions(['alwaysFakeOptionals' => true]);

        self::assertSame($server, $result);
    }
}
