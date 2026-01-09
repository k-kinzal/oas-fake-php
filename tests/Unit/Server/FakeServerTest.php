<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Server;

use GuzzleHttp\Psr7\Response;
use OasFakePHP\Config\Configuration;
use OasFakePHP\OasFake;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Server\Callback;
use OasFakePHP\Server\FakeServer;
use OasFakePHP\Vcr\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(FakeServer::class)]
#[CoversClass(Callback::class)]
final class FakeServerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        OasFake::resetInstance();
        $this->fixturesPath = __DIR__ . '/../../Fixtures/openapi';
    }

    protected function tearDown(): void
    {
        OasFake::resetInstance();
    }

    public function testStaticPropertyConfiguration(): void
    {
        $server = new class () extends FakeServer {
            protected static ?string $SCHEMA_FILE = null;
            protected static ?Mode $MODE = Mode::PASSTHROUGH;
            protected static ?bool $VALIDATE_REQUESTS = false;
            protected static ?bool $VALIDATE_RESPONSES = false;
            protected static ?string $CASSETTE_PATH = '/tmp/cassettes';
        };

        // Set schema file dynamically for test
        $server = $server->withSchemaFile($this->fixturesPath . '/petstore.yaml');

        $config = $server->getConfiguration();

        self::assertInstanceOf(Configuration::class, $config);
        self::assertSame(Mode::PASSTHROUGH, $config->getMode());
        self::assertFalse($config->shouldValidateRequests());
        self::assertFalse($config->shouldValidateResponses());
        self::assertSame('/tmp/cassettes', $config->getCassettePath());
    }

    public function testFluentApiOverridesStaticProperties(): void
    {
        $server = new class () extends FakeServer {
            protected static ?string $SCHEMA_FILE = null;
            protected static ?Mode $MODE = Mode::RECORD;
        };

        $server = $server
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->withMode(Mode::PASSTHROUGH)
            ->withCassettePath('/custom/path');

        $config = $server->getConfiguration();

        self::assertSame(Mode::PASSTHROUGH, $config->getMode());
        self::assertSame('/custom/path', $config->getCassettePath());
    }

    public function testStartAndStopWithServer(): void
    {
        $server = (new FakeServer())
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->withMode(Mode::PASSTHROUGH)
            ->withCassettePath(sys_get_temp_dir());

        self::assertFalse($server->isRunning());

        $server->start();
        self::assertTrue($server->isRunning());

        $server->stop();
        self::assertFalse($server->isRunning());
    }

    public function testOasFakeStartWithServerClass(): void
    {
        TestServer::$schemaPath = $this->fixturesPath . '/petstore.yaml';

        OasFake::start(TestServer::class);
        self::assertTrue(OasFake::isRunning());
        self::assertInstanceOf(TestServer::class, OasFake::getServer());

        OasFake::stop();
        self::assertFalse(OasFake::isRunning());
    }

    public function testOasFakeStartWithServerInstance(): void
    {
        $server = (new FakeServer())
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->withMode(Mode::PASSTHROUGH)
            ->withCassettePath(sys_get_temp_dir());

        OasFake::start($server);
        self::assertTrue(OasFake::isRunning());
        self::assertSame($server, OasFake::getServer());

        OasFake::stop();
        self::assertFalse(OasFake::isRunning());
    }

    public function testCallbackMethodRegistration(): void
    {
        $server = new class () extends FakeServer {
            protected static ?string $SCHEMA_FILE = null;

            public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
            {
                return new Response(200, [], 'custom response');
            }
        };

        $server = $server->withSchemaFile($this->fixturesPath . '/petstore.yaml');

        $registry = $server->getCallbackRegistry();
        self::assertTrue($registry->hasForOperationId('listPets'));
    }

    public function testCallbackAttributeRegistration(): void
    {
        $server = new class () extends FakeServer {
            protected static ?string $SCHEMA_FILE = null;

            #[Callback(path: '/pets/{petId}', method: 'DELETE')]
            public function deletePet(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
            {
                return new Response(204);
            }
        };

        $server = $server->withSchemaFile($this->fixturesPath . '/petstore.yaml');

        $registry = $server->getCallbackRegistry();
        // The callback should be registered for the path
        self::assertInstanceOf(CallbackRegistry::class, $registry);
    }

    public function testGetCallbackRegistry(): void
    {
        $server = new FakeServer();

        self::assertInstanceOf(CallbackRegistry::class, $server->getCallbackRegistry());
    }

    public function testRegisterCallbackFluently(): void
    {
        $server = (new FakeServer())
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->registerCallback('listPets', static fn ($req, $res) => new Response());

        self::assertTrue($server->getCallbackRegistry()->hasForOperationId('listPets'));
    }

    public function testRegisterCallbackForPathFluently(): void
    {
        $server = (new FakeServer())
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->registerCallbackForPath('/pets', 'GET', static fn ($req, $res) => new Response());

        self::assertInstanceOf(CallbackRegistry::class, $server->getCallbackRegistry());
    }

    public function testWithSchemaString(): void
    {
        $yaml = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";

        $server = (new FakeServer())
            ->withSchemaString($yaml, 'yaml')
            ->withMode(Mode::PASSTHROUGH);

        $config = $server->getConfiguration();
        self::assertTrue($config->hasSchema());
    }

    public function testWithFakerOptions(): void
    {
        $options = ['alwaysFakeOptionals' => true, 'minItems' => 1];

        $server = (new FakeServer())
            ->withSchemaFile($this->fixturesPath . '/petstore.yaml')
            ->withFakerOptions($options);

        $config = $server->getConfiguration();
        self::assertSame($options, $config->getFakerOptions());
    }
}

class TestServer extends FakeServer
{
    public static string $schemaPath = '';

    protected static ?Mode $MODE = Mode::PASSTHROUGH;

    public function __construct()
    {
        parent::__construct();
        $this->withSchemaFile(self::$schemaPath);
        $this->withCassettePath(sys_get_temp_dir());
    }
}
