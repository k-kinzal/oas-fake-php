<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use OasFake\FakeDataContext;
use OasFake\Handler;
use OasFake\HandlerMap;
use OasFake\OperationLookup;
use OasFake\OperationResponder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \OasFake\OperationResponder
 *
 * @uses \OasFake\PayloadCodec
 * @uses \OasFake\FakeDataContext
 * @uses \OasFake\FakeResponse
 * @uses \OasFake\FakeResponseFactory
 * @uses \OasFake\Handler
 * @uses \OasFake\HandlerMap
 * @uses \OasFake\OpenApiServerResolver
 * @uses \OasFake\OperationInfo
 * @uses \OasFake\OperationIndexBuilder
 * @uses \OasFake\OperationLookup
 * @uses \OasFake\OperationParameterResolver
 * @uses \OasFake\OperationResponseResolver
 * @uses \OasFake\PathOperationResolver
 * @uses \OasFake\PayloadSerializer
 * @uses \OasFake\Schema
 * @uses \OasFake\JsonHandlerBody
 * @uses \OasFake\OperationInfoFactory
 */
#[CoversClass(OperationResponder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadCodec::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponse::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Handler::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(HandlerMap::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfo::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\JsonHandlerBody::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationInfoFactory::class)]
final class OperationResponderTest extends TestCase
{
    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testRespondUsesRegisteredHandler(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationLookup($schema))->findByOperationId('listPets');
        $handlers = new HandlerMap();
        $handlers->forOperation('listPets', Handler::response(200, [['id' => 1, 'name' => 'Handled']]));

        $response = (new OperationResponder(new FakeDataContext($schema), $handlers))->respond(
            new ServerRequest('GET', 'https://api.petstore.example.com/pets'),
            '/pets',
            'GET',
            $operation,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Handled', (string) $response->getBody());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testRespondReturnsErrorWhenOperationCannotBeResolved(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');

        $response = (new OperationResponder(new FakeDataContext($schema), new HandlerMap()))->respond(
            new ServerRequest('GET', 'https://api.petstore.example.com/unknown'),
            '/unknown',
            'GET',
            null,
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"error":"Could not resolve operation from request"}', (string) $response->getBody());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testRespondPassesTheSchemaGeneratedDefaultToCallbacks(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationLookup($schema))->findByOperationId('listPets');
        $handlers = new HandlerMap();
        $handlers->forOperation('listPets', Handler::callback(
            static fn (ServerRequestInterface $request, ?ResponseInterface $default): Response => new Response($default?->getStatusCode() ?? 599),
        ));

        $response = (new OperationResponder(new FakeDataContext($schema), $handlers))->respond(
            new ServerRequest('GET', 'https://api.petstore.example.com/pets'),
            '/pets',
            'GET',
            $operation,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws JsonException when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoPath when the exercised contract propagates it
     * @throws \Vural\OpenAPIFaker\Exception\NoResponse when the exercised contract propagates it
     */
    public function testRespondPassesNullToCallbacksForOperationsWithoutABody(): void
    {
        $schema = \OasFake\Testing\SchemaFixture::fromFile(__DIR__ . '/../../Fixtures/openapi/petstore.yaml');
        $operation = (new OperationLookup($schema))->findByOperationId('deletePet');
        $handlers = new HandlerMap();
        $handlers->forOperation('deletePet', Handler::callback(
            static fn (ServerRequestInterface $request, ?ResponseInterface $default): Response => new Response($default === null ? 202 : 599),
        ));

        $response = (new OperationResponder(new FakeDataContext($schema), $handlers))->respond(
            new ServerRequest('DELETE', 'https://api.petstore.example.com/pets/1'),
            '/pets/1',
            'DELETE',
            $operation,
        );

        self::assertSame(202, $response->getStatusCode());
    }
}
