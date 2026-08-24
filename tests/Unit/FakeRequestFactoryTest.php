<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\FakeDataContext;
use OasFake\FakeRequestFactory;
use OasFake\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeRequestFactory::class)]
final class FakeRequestFactoryTest extends TestCase
{
    public function testCreateBuildsRequestFromIndexedOperation(): void
    {
        $context = new FakeDataContext(Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml'));
        $definition = $context->operationLookup()->findByOperationId('getPetById');

        self::assertNotNull($definition);
        $request = (new FakeRequestFactory())->create($context, $definition);

        self::assertSame('GET', $request->method());
        self::assertStringStartsWith('https://api.petstore.example.com/pets/', $request->url());
    }
}
