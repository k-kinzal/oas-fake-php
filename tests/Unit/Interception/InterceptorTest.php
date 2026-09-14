<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use JsonException;
use OasFake\Exception\ReplayMismatchError;
use OasFake\Exception\ValidationException;
use OasFake\Handler;
use OasFake\Interceptor;
use OasFake\Mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use VCR\Request as VcrRequest;

/**
 * @uses \OasFake\LosslessVcrResponse
 *
 * @covers \OasFake\Interceptor
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\RequestPathMatcher
 * @uses \OasFake\CassetteSession
 * @uses \OasFake\Converter
 * @uses \OasFake\Exception\ReplayMismatchError
 * @uses \OasFake\Exception\ValidationException
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\MiddlewarePipeline
 * @uses \OasFake\MiddlewareRequestHandler
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
 * @uses \OasFake\ResolvedResponseRequestHandler
 * @uses \OasFake\Schema
 * @uses \OasFake\SchemaRequestHandler
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\VcrResponseFactory
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\StringHandlerBody
 */
#[CoversClass(Interceptor::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\LosslessVcrResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\RequestPathMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Converter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ReplayMismatchError::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ValidationException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewarePipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\MiddlewareRequestHandler::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ResolvedResponseRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\VcrResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\StringHandlerBody::class)]
final class InterceptorTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testIsRunningReturnsFalseByDefault(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: true,
        );

        self::assertFalse($interceptor->isRunning());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testStartMarksInterceptorRunning(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: true,
        );

        $interceptor->start();

        self::assertTrue($interceptor->isRunning());
        $interceptor->stop();
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testStopMarksInterceptorStopped(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: true,
        );
        $interceptor->start();

        $interceptor->stop();

        self::assertFalse($interceptor->isRunning());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleReturnsFakeResponseForValidRequest(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody(), true);
        self::assertIsArray($body);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleReturnsFakeResponseForSingleResource(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets/1', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('name', $body);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleUsesStubOverFaker(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $stubBody = json_encode([['id' => 42, 'name' => 'Stubbed Pet']], JSON_THROW_ON_ERROR);
        $handlers->forOperation('listPets', Handler::response(200, $stubBody));

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($stubBody, $vcrResponse->getBody());
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleUsesPathStubOverFaker(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $stubBody = json_encode([['id' => 77, 'name' => 'Path Stub']], JSON_THROW_ON_ERROR);
        $handlers->forPath('/pets', 'GET', Handler::response(200, $stubBody));

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($stubBody, $vcrResponse->getBody());
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleUsesTemplatedPathStubOverFaker(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $stubBody = json_encode(['id' => 77, 'name' => 'Template Path Stub'], JSON_THROW_ON_ERROR);
        $handlers->forPath('/pets/{petId}', 'GET', Handler::response(200, $stubBody));

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets/123', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($stubBody, $vcrResponse->getBody());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleValidatesRequestWhenEnabled(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/nonexistent', []);

        $this->expectException(ValidationException::class);

        $interceptor->handle($vcrRequest);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleValidatesResponseWhenRequestValidationDisabled(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $handlers->forOperation('listPets', Handler::response(200, ['not' => 'an array']));
        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: true,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets?limit=invalid', []);

        $this->expectException(ValidationException::class);

        $interceptor->handle($vcrRequest);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleReturns500WhenOperationCannotBeResolved(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/nonexistent', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(500, $vcrResponse->getStatusCode());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleExecutesMiddleware(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Middleware', 'applied');
            }
        };

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
            middleware: [$middleware],
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $headers = $vcrResponse->getHeaders();
        self::assertArrayHasKey('X-Middleware', $headers);
        self::assertSame('applied', $headers['X-Middleware']);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleLetsMiddlewareRewriteRequestBeforeOperationResolution(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request->withUri(new Uri('https://api.petstore.example.com/pets')));
            }
        };

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
            middleware: [$middleware],
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/rewritten', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertIsArray(json_decode($vcrResponse->getBody(), true));
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleExecutesMiddlewareInCorrectOrder(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $first = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', ($response->getHeaderLine('X-Order')) . 'first');
            }
        };

        $second = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', ($response->getHeaderLine('X-Order')) . 'second');
            }
        };

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
            middleware: [$first, $second],
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        $headers = $vcrResponse->getHeaders();
        self::assertSame('secondfirst', $headers['X-Order']);
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleWithCallbackStub(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $callbackBody = json_encode([['id' => 1, 'name' => 'Callback Pet']], JSON_THROW_ON_ERROR);
        $handlers->forOperation('listPets', Handler::callback(
            static fn (ServerRequestInterface $request, ?ResponseInterface $default): ResponseInterface => new Response(200, ['Content-Type' => 'application/json'], $callbackBody),
        ));

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($callbackBody, $vcrResponse->getBody());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testHandleWithStatusStub(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $handlers->forOperation('listPets', Handler::status(201));

        $interceptor = new Interceptor(
            mode: Mode::FAKE,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(201, $vcrResponse->getStatusCode());
        self::assertSame('', $vcrResponse->getBody());
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testReplayReturnsMatchingRecording(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: __DIR__ . '/../../Fixtures/cassettes',
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $interceptor->start();

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $request->setHeader('Host', 'api.petstore.example.com');

        $response = $interceptor->replay($request);

        self::assertSame('[{"id":1,"name":"Buddy"}]', $response->getBody());
        $interceptor->stop();
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testReplayExecutesMiddleware(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request)->withHeader('X-Replayed', 'yes');
            }
        };

        $interceptor = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: __DIR__ . '/../../Fixtures/cassettes',
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
            middleware: [$middleware],
        );
        $interceptor->start();

        $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $request->setHeader('Host', 'api.petstore.example.com');

        $response = $interceptor->replay($request);

        self::assertSame('yes', $response->getHeaders()['X-Replayed']);
        $interceptor->stop();
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testReplayValidatesRequestWhenEnabled(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: __DIR__ . '/../../Fixtures/cassettes',
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: false,
        );
        $interceptor->start();

        $this->expectException(ValidationException::class);

        try {
            $interceptor->replay(new VcrRequest('GET', 'https://api.petstore.example.com/unknown', []));
        } finally {
            $interceptor->stop();
        }
    }

    /**
     * @throws JsonException when the response fixture cannot be encoded
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testReplayValidatesResponseWhenEnabled(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        file_put_contents($directory->path() . '/recording', json_encode([[
            'request' => [
                'method' => 'GET',
                'url' => 'https://api.petstore.example.com/pets',
                'headers' => ['Host' => 'api.petstore.example.com'],
            ],
            'response' => [
                'status' => ['http_version' => '1.1', 'code' => '200', 'message' => 'OK'],
                'headers' => ['Content-Type' => 'application/json'],
                'body' => '{"not":"an array"}',
            ],
            'index' => 0,
        ]], JSON_THROW_ON_ERROR));

        $interceptor = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: true,
            validateResponses: true,
        );
        $interceptor->start();

        $this->expectException(ValidationException::class);

        try {
            $request = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
            $request->setHeader('Host', 'api.petstore.example.com');
            $interceptor->replay($request);
        } finally {
            $interceptor->stop();
        }
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRecordModeGeneratesResponse(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::RECORD,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $interceptor->start();

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody(), true);
        self::assertIsArray($body);

        $interceptor->stop();
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRecordModeWritesCassette(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::RECORD,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $interceptor->start();

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $interceptor->handle($vcrRequest);
        $interceptor->stop();

        $cassetteFile = $directory->path() . '/recording';
        self::assertFileExists($cassetteFile);

        $recordings = json_decode((string) file_get_contents($cassetteFile), true);
        self::assertIsArray($recordings);
        self::assertCount(1, $recordings);
        $recording = $recordings[0] ?? null;
        self::assertIsArray($recording);
        $recordedRequest = $recording['request'] ?? null;
        self::assertIsArray($recordedRequest);
        self::assertSame('GET', $recordedRequest['method'] ?? null);
        $recordedUrl = $recordedRequest['url'] ?? null;
        self::assertIsString($recordedUrl);
        self::assertStringContainsString('/pets', $recordedUrl);
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRecordModeWritesConfiguredCassetteName(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');

        $interceptor = new Interceptor(
            mode: Mode::RECORD,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
            cassetteName: 'petstore-recording',
        );
        $interceptor->start();

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $interceptor->handle($vcrRequest);
        $interceptor->stop();

        self::assertFileExists($directory->path() . '/petstore-recording');
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRecordThenReplayRoundTrip(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');
        $recorder = new Interceptor(
            mode: Mode::RECORD,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $recorder->start();

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $vcrRequest->setHeader('Host', 'api.petstore.example.com');
        $recordedResponse = $recorder->handle($vcrRequest);
        $recorder->stop();
        $replayer = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $replayer->start();

        $replayRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $replayRequest->setHeader('Host', 'api.petstore.example.com');
        $replayedResponse = $replayer->replay($replayRequest);

        self::assertSame($recordedResponse->getBody(), $replayedResponse->getBody());
        self::assertSame($recordedResponse->getStatusCode(), $replayedResponse->getStatusCode());

        $replayer->stop();
    }

    /**
     * @throws \cebe\openapi\exceptions\IOException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\TypeErrorException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException when the schema fixture cannot be loaded
     * @throws \cebe\openapi\json\InvalidJsonPointerSyntaxException when the schema fixture cannot be loaded
     */
    public function testRecordThenReplayWithDifferentBodiesSameUrl(): void
    {
        $schema = \OasFake\Schema::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $handlers = new \OasFake\HandlerMap();
        $directory = new \Tests\Fixtures\TemporaryDirectory('oas-fake-test-cassettes');
        $recorder = new Interceptor(
            mode: Mode::RECORD,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $recorder->start();

        $reqA = new VcrRequest('POST', 'https://api.petstore.example.com/pets', []);
        $reqA->setHeader('Host', 'api.petstore.example.com');
        $reqA->setHeader('Content-Type', 'application/json');
        $reqA->setBody('{"name":"A"}');
        $respA = $recorder->handle($reqA);

        $reqB = new VcrRequest('POST', 'https://api.petstore.example.com/pets', []);
        $reqB->setHeader('Host', 'api.petstore.example.com');
        $reqB->setHeader('Content-Type', 'application/json');
        $reqB->setBody('{"name":"B"}');
        $respB = $recorder->handle($reqB);

        $recorder->stop();
        $replayer = new Interceptor(
            mode: Mode::REPLAY,
            cassettePath: $directory->path(),
            schema: $schema,
            validator: new \OasFake\Validator($schema),
            fakerOptions: [],
            handlers: $handlers,
            validateRequests: false,
            validateResponses: false,
        );
        $replayer->start();

        $replayA = new VcrRequest('POST', 'https://api.petstore.example.com/pets', []);
        $replayA->setHeader('Host', 'api.petstore.example.com');
        $replayA->setHeader('Content-Type', 'application/json');
        $replayA->setBody('{"name":"A"}');
        $resultA = $replayer->replay($replayA);
        self::assertSame($respA->getBody(), $resultA->getBody());

        $replayB = new VcrRequest('POST', 'https://api.petstore.example.com/pets', []);
        $replayB->setHeader('Host', 'api.petstore.example.com');
        $replayB->setHeader('Content-Type', 'application/json');
        $replayB->setBody('{"name":"B"}');
        $resultB = $replayer->replay($replayB);
        self::assertSame($respB->getBody(), $resultB->getBody());

        $replayer->stop();
    }
}
