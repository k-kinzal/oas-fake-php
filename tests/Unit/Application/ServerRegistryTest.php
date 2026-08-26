<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Server;
use OasFake\ServerRegistry;
use OasFake\Testing\InspectableServer;
use OasFake\Testing\ServerRegistryContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use VCR\Request as VcrRequest;

/**
 * @covers \OasFake\ServerRegistry
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\CassetteNameNormalizer
 * @uses \OasFake\CassetteSession
 * @uses \OasFake\Converter
 * @uses \OasFake\DeclarativeHandlerInspector
 * @uses \OasFake\DeclarativeHandlerRegistrar
 * @uses \OasFake\EnvironmentResolver
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\Interceptor
 * @uses \OasFake\InterceptorFactory
 * @uses \OasFake\InterceptorRouter
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
 * @uses \OasFake\Server
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 * @uses \OasFake\ServerOptions
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\VcrLifecycle
 * @uses \OasFake\VcrResponseFactory
 * @uses \OasFake\ServerMiddleware
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\ServerRuntime
 */
#[CoversClass(ServerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteNameNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\EnvironmentResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorRouter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Mode::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerOptions::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerRuntime::class)]
final class ServerRegistryTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        self::assertTrue($registry->isEmpty());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testRegisterAndGet(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = new InspectableServer();

        $registry->register('TestServer', $server);

        self::assertFalse($registry->isEmpty());
        self::assertSame($server, $registry->get('TestServer'));
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        self::assertNull($registry->get('Unknown'));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregister(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = new InspectableServer();

        $registry->register('TestServer', $server);
        $registry->unregister('TestServer');

        self::assertTrue($registry->isEmpty());
        self::assertNull($registry->get('TestServer'));
        self::assertSame(1, $server->unregisterCount);
    }

    public function testUnregisterNonExistentDoesNothing(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $registry->unregister('Unknown');
        $this->addToAssertionCount(1);
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregisterAll(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server1 = new InspectableServer();
        $server2 = new InspectableServer();

        $registry->register('Server1', $server1);
        $registry->register('Server2', $server2);

        $registry->unregisterAll();

        self::assertTrue($registry->isEmpty());
        self::assertSame(1, $server1->unregisterCount);
        self::assertSame(1, $server2->unregisterCount);
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testReRegisterSameKeyReplacesServer(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server1 = new InspectableServer();
        $server2 = new InspectableServer();

        $registry->register('TestServer', $server1);
        $registry->register('TestServer', $server2);

        self::assertSame($server2, $registry->get('TestServer'));
        self::assertSame(1, $server1->unregisterCount);
        self::assertSame(0, $server2->unregisterCount);
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregisterAllStopsServersAndClearsRoutes(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();
        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);
        $registry->register('PetServer', $server);

        $registry->unregisterAll();

        self::assertFalse($server->isRunning());
        $response = $registry->dispatch(new VcrRequest('GET', 'https://api.petstore.example.com/pets', []));
        self::assertSame(502, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchRoutesToCorrectServer(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $petServer = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Buddy']]);

        $bookServer = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/bookstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listBooks', 200, [['id' => 1, 'title' => 'PHP in Action']]);

        $registry->register('PetServer', $petServer);
        $registry->register('BookServer', $bookServer);

        $petRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $petResponse = $registry->dispatch($petRequest);
        self::assertSame(200, $petResponse->getStatusCode());
        self::assertStringContainsString('Buddy', $petResponse->getBody());

        $bookRequest = new VcrRequest('GET', 'https://api.bookstore.example.com/books', []);
        $bookResponse = $registry->dispatch($bookRequest);
        self::assertSame(200, $bookResponse->getStatusCode());
        self::assertStringContainsString('PHP in Action', $bookResponse->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchUsesMostRecentServerForSameUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $first = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'First']]);

        $second = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 2, 'name' => 'Second']]);

        $registry->register('FirstServer', $first);
        $registry->register('SecondServer', $second);

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Second', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregisterOlderServerKeepsNewerServerForSameUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $first = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'First']]);

        $second = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 2, 'name' => 'Second']]);

        $registry->register('FirstServer', $first);
        $registry->register('SecondServer', $second);
        $registry->unregister('FirstServer');

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Second', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testUnregisterNewerServerRestoresOlderServerForSameUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $first = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'First']]);

        $second = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 2, 'name' => 'Second']]);

        $registry->register('FirstServer', $first);
        $registry->register('SecondServer', $second);
        $registry->unregister('SecondServer');

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('First', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testServerStopUnregistersFromOwningRegistry(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $first = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'First']]);

        $second = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 2, 'name' => 'Second']]);

        $registry->register('FirstServer', $first);
        $registry->register('SecondServer', $second);

        $second->stop();

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('First', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchReturns502ForUnknownUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $registry->register('PetServer', $server);

        $request = new VcrRequest('GET', 'https://unknown.example.com/foo', []);
        $response = $registry->dispatch($request);

        self::assertSame(502, $response->getStatusCode());
        self::assertSame('application/json', $response->getContentType());
        self::assertSame(
            ['error' => 'No OasFake server registered for: https://unknown.example.com/foo'],
            json_decode($response->getBody(), true),
        );
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchDoesNotMatchSimilarHostPrefix(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $registry->register('PetServer', $server);

        $request = new VcrRequest('GET', 'https://api.petstore.example.com.evil/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(502, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchDoesNotMatchPathPrefixWithoutSegmentBoundary(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $registry->register('VersionedServer', $server);

        $request = new VcrRequest('GET', 'https://api.versioned.example.com/v10/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(502, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchUsesMostSpecificMatchingServerUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $rootServer = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/root-versioned-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listRootPets', 200, [['id' => 1, 'name' => 'Root']]);

        $versionedServer = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 2, 'name' => 'Versioned']]);

        $registry->register('RootServer', $rootServer);
        $registry->register('VersionedServer', $versionedServer);

        $request = new VcrRequest('GET', 'https://api.versioned.example.com/v1/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Versioned', $response->getBody());
        self::assertStringNotContainsString('Root', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchStripsServerBasePathForOperationLookup(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/versioned-petstore.yaml');

        $registry->register('VersionedServer', $server);

        $request = new VcrRequest('GET', 'https://api.versioned.example.com/v1/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray(json_decode($response->getBody(), true));
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchRoutesPathLevelServerUrl(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/path-server-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Path Server']]);

        $registry->register('PathServer', $server);

        $request = new VcrRequest('GET', 'https://api.path-petstore.example.com/v1/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Path Server', $response->getBody());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchDoesNotMatchRootServerOverriddenByPathServer(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/path-server-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        $registry->register('PathServer', $server);

        $request = new VcrRequest('GET', 'https://root.petstore.example.com/pets', []);
        $response = $registry->dispatch($request);

        self::assertSame(502, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testDispatchDoesNotServePathLevelOperationThroughRootServer(): void
    {
        $registryContext = new ServerRegistryContext();
        $registry = $registryContext->registry();

        $server = (new Server())
            ->withSchema(__DIR__ . '/../../Fixtures/openapi/mixed-server-petstore.yaml')
            ->withRequestValidation(false)
            ->withResponseValidation(false)
            ->withResponse('listPets', 200, [['id' => 1, 'name' => 'Path Server']])
            ->withResponse('listOrders', 200, [['id' => 10]]);

        $registry->register('MixedServer', $server);

        $rootOrders = new VcrRequest('GET', 'https://root.petstore.example.com/orders', []);
        $ordersResponse = $registry->dispatch($rootOrders);
        self::assertSame(200, $ordersResponse->getStatusCode());

        $rootPets = new VcrRequest('GET', 'https://root.petstore.example.com/pets', []);
        $petsResponse = $registry->dispatch($rootPets);

        self::assertSame(500, $petsResponse->getStatusCode());
        self::assertStringNotContainsString('Path Server', $petsResponse->getBody());
    }
}
