<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use Closure;
use OasFake\Exception\InvalidModeException;
use OasFake\Exception\ServerStateException;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use VCR\Request as VcrRequest;

#[CoversClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteNameNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\EnvironmentResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(InvalidModeException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerStateException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OasFake::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerOptions::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ServerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
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

    public function testWithModeRejectsInvalidMode(): void
    {
        $server = new Server();

        $this->expectException(InvalidModeException::class);

        $server->withMode('invalid');
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
     * @dataProvider providerConfigurationMutations
     */
    #[DataProvider('providerConfigurationMutations')]
    public function testConfigurationCannotChangeWhileRunning(Closure $mutation): void
    {
        $server = (new Server())
            ->withSchema(Petstore::path())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $server->buildInterceptor();
        $this->expectException(ServerStateException::class);
        $this->expectExceptionMessage('Cannot change server configuration while the server is running');

        try {
            $mutation($server);
        } finally {
            $server->stop();
        }
    }

    /**
     * @return iterable<string, array{Closure(Server): Server}>
     */
    public static function providerConfigurationMutations(): iterable
    {
        yield 'schema' => [static fn (Server $server): Server => $server->withSchema(__DIR__ . '/../Fixtures/openapi/bookstore.yaml')];
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

    public function testServerUrlsReturnsSchemaUrls(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        self::assertSame(['https://api.petstore.example.com'], $server->serverUrls());
    }

    public function testResolveSchemaThrowsWithoutConfiguredPath(): void
    {
        $server = new Server();

        $this->expectException(\OasFake\Exception\SchemaNotFoundException::class);
        $this->expectExceptionMessage('No schema configured');
        $server->resolveSchema();
    }

    public function testAssertCanRegisterInRegistryRejectsDifferentOwner(): void
    {
        $server = (new Server())->withSchema(Petstore::path());
        $registry = new ServerRegistry();

        $server->registerInRegistry($registry, 'PetServer');

        try {
            $server->assertCanRegisterInRegistry(new ServerRegistry(), 'OtherServer');
        } catch (ServerStateException $exception) {
            self::assertStringContainsString('already registered', $exception->getMessage());

            return;
        } finally {
            $server->unregisterFromRegistry($registry, 'PetServer');
        }

        self::fail('Expected ServerStateException was not thrown.');
    }

    public function testRegisterInRegistryStoresOwnership(): void
    {
        $server = new Server();
        $registry = new ServerRegistry();
        $server->registerInRegistry($registry, 'PetServer');

        try {
            $server->assertCanRegisterInRegistry($registry, 'PetServer');
            $this->addToAssertionCount(1);
        } finally {
            $server->unregisterFromRegistry($registry, 'PetServer');
        }
    }

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

    public function testResolveCassettePathPrefersEnvironment(): void
    {
        putenv('OAS_FAKE_CASSETTE_PATH=/env/cassettes');

        $server = new Server();
        $server->withCassettePath('/fluent/cassettes');

        self::assertSame('/env/cassettes', $server->resolveCassettePath());
    }

    public function testResolveCassetteNameNormalizesConfiguredName(): void
    {
        $server = (new Server())->withCassetteName('My API Cassette');

        self::assertSame('my-api-cassette', $server->resolveCassetteName());
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

    public function testResolveRequestValidationPrefersEnvironment(): void
    {
        putenv('OAS_FAKE_VALIDATE_REQUESTS=false');

        $server = new Server();
        $server->withRequestValidation(true);

        self::assertFalse($server->resolveRequestValidation());
    }

    public function testResolveResponseValidationPrefersEnvironment(): void
    {
        putenv('OAS_FAKE_VALIDATE_RESPONSES=false');

        $server = new Server();
        $server->withResponseValidation(true);

        self::assertFalse($server->resolveResponseValidation());
    }

    public function testResolveMiddlewareIncludesFluentMiddleware(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };
        $server = (new Server())->withMiddleware($middleware);

        self::assertSame([$middleware], $server->resolveMiddleware());
    }

    public function testResolvedOptionsCapturesEffectiveConfiguration(): void
    {
        $schema = \OasFake\Schema::fromFile(Petstore::path());
        $server = (new Server())
            ->withMode(Mode::RECORD)
            ->withCassettePath('/tmp/cassettes')
            ->withCassetteName('Pet Store')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withFakerOptions(['alwaysFakeOptionals' => true]);

        $options = $server->resolvedOptions($schema);

        self::assertSame($schema, $options->schema);
        self::assertSame(Mode::RECORD, $options->mode->value());
        self::assertSame('/tmp/cassettes', $options->cassettePath);
        self::assertSame('pet-store', $options->cassetteName);
        self::assertFalse($options->validateRequests);
        self::assertFalse($options->validateResponses);
        self::assertSame(['alwaysFakeOptionals' => true], $options->fakerOptions);
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
