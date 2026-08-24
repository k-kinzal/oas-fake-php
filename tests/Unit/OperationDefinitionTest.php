<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Operation;
use OasFake\OperationDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationDefinition::class)]
final class OperationDefinitionTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testExposesRoutingMetadata(): void
    {
        $operation = new Operation(['responses' => []]);
        $definition = new OperationDefinition('/pets', 'get', 'listPets', $operation, [], ['https://api.example.com']);

        self::assertSame('/pets', $definition->pathPattern);
        self::assertSame('get', $definition->method);
        self::assertSame('listPets', $definition->operationId);
        self::assertSame($operation, $definition->operation);
        self::assertSame([], $definition->parameters);
        self::assertSame(['https://api.example.com'], $definition->serverUrls);
    }

    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testDefaultsServerScopeToTheRootUrl(): void
    {
        $definition = new OperationDefinition('/pets', 'get', 'listPets', new Operation(['responses' => []]), []);

        self::assertSame(['/'], $definition->serverUrls);
    }
}
