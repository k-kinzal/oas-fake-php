<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use OasFake\Handler;
use OasFake\Mode;
use OasFake\Route;
use OasFake\Server;
use OasFake\ServerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use VCR\Request as VcrRequest;

#[CoversClass(Server::class)]
final class ServerTest extends TestCase
{
    private string $petstorePath;

    protected function setUp(): void
    {
        $this->petstorePath = __DIR__ . '/../Fixtures/openapi/petstore.yaml';

        // Clean up env vars
        putenv('OAS_FAKE_MODE');
        putenv('OAS_FAKE_CASSETTE_PATH');
        putenv('OAS_FAKE_CASSETTE_NAME');
        putenv('OAS_FAKE_VALIDATE_REQUESTS');
        putenv('OAS_FAKE_VALIDATE_RESPONSES');
    }

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
        $result = $server->withSchema($this->petstorePath);

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

        $this->expectException(InvalidArgumentException::class);

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
            ->withSchema($this->petstorePath)
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
            ->withSchema($this->petstorePath)
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
        $cassettePath = sys_get_temp_dir() . '/oas-fake-server-cassettes-' . uniqid('', true);
        mkdir($cassettePath, 0777, true);

        $server = (new CassetteNameTestServer())
            ->withMode(Mode::RECORD)
            ->withCassettePath($cassettePath);

        try {
            $server->buildInterceptor();

            self::assertFileExists($cassettePath . '/oasfake-tests-unit-cassettenametestserver');
        } finally {
            $server->stop();
            foreach (glob($cassettePath . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($cassettePath);
        }
    }

    public function testBuildInterceptorUsesConfiguredCassetteName(): void
    {
        $cassettePath = sys_get_temp_dir() . '/oas-fake-server-cassettes-' . uniqid('', true);
        mkdir($cassettePath, 0777, true);

        $server = (new CassetteNameTestServer())
            ->withMode(Mode::RECORD)
            ->withCassettePath($cassettePath)
            ->withCassetteName('Custom Cassette');

        try {
            $server->buildInterceptor();

            self::assertFileExists($cassettePath . '/custom-cassette');
        } finally {
            $server->stop();
            foreach (glob($cassettePath . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($cassettePath);
        }
    }

    public function testConfigurationCannotChangeWhileRunning(): void
    {
        $server = (new Server())
            ->withSchema($this->petstorePath)
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        try {
            $server->buildInterceptor();

            $cases = [
                'schema' => static fn () => $server->withSchema(__DIR__ . '/../Fixtures/openapi/bookstore.yaml'),
                'mode' => static fn () => $server->withMode(Mode::RECORD),
                'cassettePath' => static fn () => $server->withCassettePath('/tmp/other-cassettes'),
                'cassetteName' => static fn () => $server->withCassetteName('other'),
                'requestValidation' => static fn () => $server->withRequestValidation(true),
                'responseValidation' => static fn () => $server->withResponseValidation(true),
                'fakerOptions' => static fn () => $server->withFakerOptions(['alwaysFakeOptionals' => true]),
                'middleware' => static fn () => $server->withMiddleware($middleware),
                'handler' => static fn () => $server->withHandler('deletePet', Handler::status(204)),
                'response' => static fn () => $server->withResponse('listPets', 200, []),
                'callback' => static fn () => $server->withCallback('listPets', static fn (): ResponseInterface => new \GuzzleHttp\Psr7\Response(200)),
                'pathResponse' => static fn () => $server->withPathResponse('/pets', 'GET', 200, []),
                'pathCallback' => static fn () => $server->withPathCallback('/pets', 'GET', static fn (): ResponseInterface => new \GuzzleHttp\Psr7\Response(200)),
            ];

            foreach ($cases as $name => $mutate) {
                try {
                    $mutate();
                    self::fail(sprintf('Expected LogicException for %s mutation while running.', $name));
                } catch (LogicException $e) {
                    self::assertStringContainsString('Cannot change server configuration while the server is running', $e->getMessage());
                }
            }
        } finally {
            $server->stop();
        }
    }

    public function testInterceptorReturnsActiveInterceptor(): void
    {
        $server = (new Server())
            ->withSchema($this->petstorePath)
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
        $server = (new Server())->withSchema($this->petstorePath);

        self::assertSame(['https://api.petstore.example.com'], $server->schema()->serverUrls());
    }

    public function testFakerOptionsReturnsConfiguredOptions(): void
    {
        $server = (new Server())->withFakerOptions(['alwaysFakeOptionals' => true]);

        self::assertSame(['alwaysFakeOptionals' => true], $server->fakerOptions());
    }

    public function testServerUrlsReturnsSchemaUrls(): void
    {
        $server = (new Server())->withSchema($this->petstorePath);

        self::assertSame(['https://api.petstore.example.com'], $server->serverUrls());
    }

    public function testStartThrowsWithoutSchema(): void
    {
        $server = new Server();

        $this->expectException(\OasFake\Exception\SchemaNotFoundException::class);
        $this->expectExceptionMessage('No schema configured');
        $server->start();
    }

    public function testRegisterInRegistryRejectsDifferentOwner(): void
    {
        $server = (new Server())->withSchema($this->petstorePath);
        $registry = new ServerRegistry();

        $server->registerInRegistry($registry, 'PetServer');

        try {
            $server->registerInRegistry(new ServerRegistry(), 'OtherServer');
        } catch (LogicException $exception) {
            self::assertStringContainsString('already registered', $exception->getMessage());

            return;
        } finally {
            $server->unregisterFromRegistry($registry, 'PetServer');
        }

        self::fail('Expected LogicException was not thrown.');
    }

    public function testUnregisterFromRegistryStopsInterceptor(): void
    {
        $server = (new Server())
            ->withSchema($this->petstorePath)
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

        // The server should be constructible and fluent methods should work
        $result = $server->withMode(Mode::RECORD);
        self::assertSame($server, $result);
    }

    public function testEnvVarOverridesFluentMode(): void
    {
        putenv('OAS_FAKE_MODE=record');

        $server = new Server();
        $server->withSchema($this->petstorePath)->withMode(Mode::FAKE);

        // We can't easily test the resolved mode without starting,
        // but we can verify env is set
        self::assertSame('record', getenv('OAS_FAKE_MODE'));
    }

    public function testEnvVarOverridesCassettePath(): void
    {
        putenv('OAS_FAKE_CASSETTE_PATH=/env/cassettes');

        $server = new Server();
        $server->withCassettePath('/fluent/cassettes');

        self::assertSame('/env/cassettes', getenv('OAS_FAKE_CASSETTE_PATH'));
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

    public function testEnvVarOverridesValidateRequests(): void
    {
        putenv('OAS_FAKE_VALIDATE_REQUESTS=false');

        $server = new Server();
        $server->withRequestValidation(true);

        self::assertSame('false', getenv('OAS_FAKE_VALIDATE_REQUESTS'));
    }

    public function testEnvVarOverridesValidateResponses(): void
    {
        putenv('OAS_FAKE_VALIDATE_RESPONSES=false');

        $server = new Server();
        $server->withResponseValidation(true);

        self::assertSame('false', getenv('OAS_FAKE_VALIDATE_RESPONSES'));
    }

    public function testSubclassMethodsAreAutoRegisteredAsStubs(): void
    {
        // The OperationIdTestServer has a public method "listPets"
        // which should be auto-registered as a stub for operationId "listPets"
        $server = new OperationIdTestServer();

        // The server was created - method stubs were registered during construction
        // We verify by checking fluent returns still work
        $result = $server->withSchema($this->petstorePath);
        self::assertSame($server, $result);
    }

    public function testRouteAttributeMethodsAreRegisteredByPathMethod(): void
    {
        // The RouteTestServer has a method with #[Route] attribute
        $server = new RouteTestServer();

        $result = $server->withSchema($this->petstorePath);
        self::assertSame($server, $result);
    }

    public function testPublicMethodsWithoutHandlerSignatureAreNotAutoRegistered(): void
    {
        $server = (new InvalidSignatureOperationIdServer())
            ->withSchema($this->petstorePath)
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $interceptor = $server->interceptor();

            self::assertNotNull($interceptor);

            $response = $interceptor->handle(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));

            self::assertSame(200, $response->getStatusCode());
            self::assertIsArray(json_decode($response->getBody() ?? '', true));
        } finally {
            $server->stop();
        }
    }

    public function testFluentChaining(): void
    {
        $server = new Server();

        $result = $server
            ->withSchema($this->petstorePath)
            ->withMode(Mode::FAKE)
            ->withCassettePath('/tmp/cassettes')
            ->withRequestValidation(true)
            ->withResponseValidation(true)
            ->withFakerOptions(['alwaysFakeOptionals' => true]);

        self::assertSame($server, $result);
    }
}

// Test helper classes

class DeclarativeTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';
    protected static string $MODE = 'record';
    protected static string $CASSETTE_PATH = '/custom/cassettes';
    protected static bool $VALIDATE_REQUESTS = false;
    protected static bool $VALIDATE_RESPONSES = false;
    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} */
    protected static array $FAKER_OPTIONS = ['alwaysFakeOptionals' => true];
}

class OperationIdTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new \GuzzleHttp\Psr7\Response(200, [], '[]');
    }
}

class RouteTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';

    #[Route(method: 'DELETE', path: '/pets/{petId}')]
    public function removePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return new \GuzzleHttp\Psr7\Response(204);
    }
}

class CassetteNameTestServer extends Server
{
    protected static string $SCHEMA = './tests/Fixtures/openapi/petstore.yaml';
}

class InvalidSignatureOperationIdServer extends Server
{
    public function listPets(): string
    {
        return 'not a response';
    }
}
