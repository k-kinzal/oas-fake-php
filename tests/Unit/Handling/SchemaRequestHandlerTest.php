<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\Exception\ValidationException;
use OasFake\FakeDataContext;
use OasFake\Handler;
use OasFake\HandlerMap;
use OasFake\OperationLookup;
use OasFake\OperationPathResolver;
use OasFake\OperationRequest;
use OasFake\OperationRequestResolver;
use OasFake\OperationResponder;
use OasFake\Schema;
use OasFake\SchemaRequestHandler;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\SchemaRequestHandler
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\HandlerMap
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
 * @uses \OasFake\ServerUrlMatcher
 * @uses \OasFake\Validator
 * @uses \OasFake\OperationInfoFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\Exception\ValidationException
 */
#[CoversClass(SchemaRequestHandler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationPathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationRequest::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationRequestResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationResponder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\ServerUrlMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Validator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ValidationException::class)]
final class SchemaRequestHandlerTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testHandleGeneratesSchemaResponse(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, new HandlerMap()), $validator, false);

        $response = $handler->handle(new ServerRequest('GET', 'https://api.petstore.example.com/pets'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testValidateResponseSkipsAbsentAddressWhenDisabled(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, new HandlerMap()), $validator, false);

        $handler->validateResponse(new OperationRequest('/unknown', 'GET', null, null), new Response(200));

        $this->addToAssertionCount(1);
    }

    public function testValidateResponseSkipsAnAddressWhenValidationIsDisabled(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, new HandlerMap()), $validator, false);

        $handler->validateResponse(
            new OperationRequest('/pets', 'GET', null, new OperationAddress('/pets', 'get')),
            new Response(418),
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testHandleEnforcesResponseValidationWhenEnabled(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $handlers = new HandlerMap();
        $handlers->forOperation('listPets', Handler::response(200, ['invalid' => true]));
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, $handlers), $validator, true);

        $this->expectException(ValidationException::class);
        $handler->handle(new ServerRequest('GET', 'https://api.petstore.example.com/pets'));
    }
}
