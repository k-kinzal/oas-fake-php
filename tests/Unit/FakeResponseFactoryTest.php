<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Exception\FakeGenerationException;
use OasFake\FakeDataContext;
use OasFake\FakeResponseFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeResponseFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeGenerationException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FakeDataContext::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OpenApiServerResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationIndexBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationLookup::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationParameterResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\OperationResponseResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PathOperationResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\PayloadSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class FakeResponseFactoryTest extends TestCase
{
    public function testCreateUsesDeclaredStatusMediaTypeAndSchema(): void
    {
        $context = new FakeDataContext(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml'));
        $response = (new FakeResponseFactory())->create($context, '/pets', 'get', 200);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertJson((string) $response->getBody());
    }

    public function testCreateWrapsFakerFailureInDomainException(): void
    {
        $context = new FakeDataContext(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml'));

        $this->expectException(FakeGenerationException::class);
        (new FakeResponseFactory())->create($context, '/unknown', 'get', 200);
    }
}
