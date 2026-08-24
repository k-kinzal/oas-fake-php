<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use OasFake\FakeDataContext;
use OasFake\Handler;
use OasFake\HandlerMap;
use OasFake\OperationLookup;
use OasFake\OperationResponder;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
final class OperationResponderTest extends TestCase
{
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
        self::assertStringContainsString('Could not resolve operation', (string) $response->getBody());
    }
}
