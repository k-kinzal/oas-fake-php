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
final class SchemaRequestHandlerTest extends TestCase
{
    public function testHandleGeneratesSchemaResponse(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, new HandlerMap()), $validator, false);

        $response = $handler->handle(new ServerRequest('GET', 'https://api.petstore.example.com/pets'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testValidateResponseSkipsAbsentAddressWhenDisabled(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $validator = new Validator($schema);
        $context = new FakeDataContext($schema);
        $resolver = new OperationRequestResolver($schema, new OperationLookup($schema), new OperationPathResolver(), $validator, false);
        $handler = new SchemaRequestHandler($resolver, new OperationResponder($context, new HandlerMap()), $validator, false);

        $handler->validateResponse(new OperationRequest('/unknown', 'GET', null, null), new Response(200));

        $this->addToAssertionCount(1);
    }
}
