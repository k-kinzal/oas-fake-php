<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OasFake\FakeDataContext;
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
final class SchemaRequestHandlerTest extends TestCase
{
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
}
