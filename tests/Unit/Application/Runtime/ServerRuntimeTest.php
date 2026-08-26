<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\EnvironmentResolver;
use OasFake\Mode;
use OasFake\Server;
use OasFake\ServerRuntime;
use OasFake\Testing\Petstore;
use OasFake\Testing\SchemaAwareOperationServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use VCR\Request as VcrRequest;

/**
 * @covers \OasFake\ServerRuntime
 *
 * @uses \OasFake\CassetteNameNormalizer
 * @uses \OasFake\CassetteSession
 * @uses \OasFake\Converter
 * @uses \OasFake\DeclarativeHandlerInspector
 * @uses \OasFake\DeclarativeHandlerRegistrar
 * @uses \OasFake\EnvironmentResolver
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\HandlerTypeMatcher
 * @uses \OasFake\Interceptor
 * @uses \OasFake\InterceptorFactory
 * @uses \OasFake\Mode
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\OperationPathResolver
 * @uses \OasFake\OperationRequest
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\OperationResponseResolver
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\Schema
 * @uses \OasFake\Server
 * @uses \OasFake\ServerConfiguration
 * @uses \OasFake\ServerLifecycle
 * @uses \OasFake\ServerOptions
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\ServerMiddleware
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\MiddlewarePipeline
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationRequestResolver
 * @uses \OasFake\OperationResponder
 * @uses \OasFake\SchemaRequestHandler
 * @uses \OasFake\Validator
 * @uses \OasFake\VcrResponseFactory
 */
#[CoversClass(ServerRuntime::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteNameNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\DeclarativeHandlerRegistrar::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(EnvironmentResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerTypeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\InterceptorFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Server::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerLifecycle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerOptions::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
final class ServerRuntimeTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testForgetSchemaResolvesTheConfiguredReplacement(): void
    {
        $server = (new Server())->withSchema(Petstore::path());
        $original = $server->schema();

        $server->withSchema(__DIR__ . '/../../../Fixtures/openapi/bookstore.yaml');

        self::assertNotSame($original, $server->schema());
    }

    /**
     * @throws ReflectionException when a declarative handler cannot be bound
     * @throws \cebe\openapi\exceptions\IOException when the schema file cannot be read
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema has an invalid structure
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     */
    public function testBuildStartsTheResolvedInterceptor(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        try {
            $server->buildInterceptor();

            self::assertTrue($server->isRunning());
        } finally {
            $server->stop();
        }
    }

    /**
     * @throws ReflectionException when a declarative handler cannot be bound
     * @throws \cebe\openapi\exceptions\IOException when the schema file cannot be read
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema has an invalid structure
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when a schema reference cannot be resolved
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when a JSON pointer is invalid
     */
    public function testBuildRegistersDeclarativeHandlersInTheRuntime(): void
    {
        $server = (new SchemaAwareOperationServer())
            ->withRequestValidation(false)
            ->withResponseValidation(false);

        try {
            $server->buildInterceptor();
            $response = $server->interceptor()?->handle(
                new VcrRequest('GET', 'https://api.petstore.example.com/pets', []),
            );

            self::assertNotNull($response);
            self::assertStringContainsString('Declarative operation', $response->getBody());
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
    public function testSchemaCachesTheResolvedDefinition(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        self::assertSame($server->schema(), $server->schema());
    }

    public function testFakerOptionsMergesFluentOverrides(): void
    {
        $server = (new Server())->withSchema(Petstore::path())->withFakerOptions(['minItems' => 2]);

        self::assertSame(['minItems' => 2], $server->fakerOptions());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\TypeErrorException when the exercised contract propagates it
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the exercised contract propagates it
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the exercised contract propagates it
     */
    public function testServerUrlsReadsTheResolvedSchema(): void
    {
        $server = (new Server())->withSchema(Petstore::path());

        self::assertSame(['https://api.petstore.example.com'], $server->serverUrls());
    }

    public function testModeResolvesTheConfiguredEnvironment(): void
    {
        $server = (new Server())->withMode(Mode::REPLAY);

        self::assertSame(
            (new EnvironmentResolver())->string('OAS_FAKE_MODE', Mode::REPLAY),
            $server->resolveMode()->value(),
        );
    }
}
