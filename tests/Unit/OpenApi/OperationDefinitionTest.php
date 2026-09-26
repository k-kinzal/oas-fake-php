<?php

declare(strict_types=1);

namespace Tests\Unit;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use OasFake\OperationDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\OperationDefinition
 */
#[CoversClass(OperationDefinition::class)]
final class OperationDefinitionTest extends TestCase
{
    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testPathPatternBindsIdentityToTheSourceOperation(): void
    {
        $operation = new Operation(['operationId' => 'getPetById', 'responses' => []]);
        $parameter = new Parameter(['name' => 'petId', 'in' => 'path']);
        $definition = new OperationDefinition('/pets/{petId}', 'get', $operation, [$parameter]);

        self::assertSame('/pets/{petId}', $definition->pathPattern());
        self::assertSame('get', $definition->method());
        self::assertSame('getPetById', $definition->operationId());
        self::assertSame([$parameter], $definition->parameters());
        self::assertSame(['/'], $definition->serverUrls());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testOperationIdKeepsIndexedIdentityWhenTheSourceOperationChanges(): void
    {
        $operation = new Operation(['operationId' => 'listPets', 'responses' => []]);
        $definition = new OperationDefinition('/pets', 'get', $operation, [], ['https://pets.example.com']);

        $operation->operationId = 'renamedOperation';

        self::assertSame('listPets', $definition->operationId());
        self::assertSame(['https://pets.example.com'], $definition->serverUrls());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testRequestBodyExposesTheDeclaredSchema(): void
    {
        $operation = new Operation([
            'requestBody' => [
                'content' => ['application/json' => ['schema' => ['type' => 'object']]],
            ],
            'responses' => ['201' => ['description' => 'Pet created']],
        ]);
        $definition = new OperationDefinition('/pets', 'post', $operation, []);

        self::assertSame($operation->requestBody, $definition->requestBody());
        self::assertNotNull($definition->requestBody());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testOperationIdRepresentsAnUnnamedOperation(): void
    {
        $definition = new OperationDefinition('/pets', 'get', new Operation([]), []);

        self::assertSame('', $definition->operationId());
        self::assertNull($definition->requestBody());
        self::assertSame([], $definition->responses());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testParametersCannotBeReplacedThroughTheAccessor(): void
    {
        $pathParameter = new Parameter(['name' => 'petId', 'in' => 'path']);
        $definition = new OperationDefinition('/pets/{petId}', 'get', new Operation([]), [$pathParameter]);
        $parameters = $definition->parameters();

        $parameters[] = new Parameter(['name' => 'limit', 'in' => 'query']);

        self::assertCount(2, $parameters);
        self::assertSame([$pathParameter], $definition->parameters());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testMethodIdentifiesTheOperationWithinItsPath(): void
    {
        $definition = new OperationDefinition('/pets', 'post', new Operation([]), []);

        self::assertSame('post', $definition->method());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testServerUrlsPreserveEffectiveServerOrder(): void
    {
        $definition = new OperationDefinition('/pets', 'get', new Operation([]), [], [
            'https://primary.example.com',
            'https://secondary.example.com',
        ]);

        self::assertSame([
            'https://primary.example.com',
            'https://secondary.example.com',
        ], $definition->serverUrls());
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testResponsesPreserveStatusKeysAndDoNotExposeTheMutableCollection(): void
    {
        $operation = new Operation([
            'responses' => [
                '201' => ['description' => 'Pet created'],
                'default' => ['$ref' => '#/components/responses/Error'],
            ],
        ]);
        $definition = new OperationDefinition('/pets', 'post', $operation, []);
        $responses = $operation->responses;
        self::assertNotNull($responses);
        $declaredResponses = $responses->getResponses();

        $responses->removeResponse('201');

        self::assertSame($declaredResponses, $definition->responses());
        self::assertSame([201, 'default'], array_keys($definition->responses()));
    }

    /**
     * @throws TypeErrorException when operation metadata is invalid
     */
    public function testRequestBodyDoesNotExposeAnUnresolvedReferenceAsAResolvedDeclaration(): void
    {
        $operation = new Operation([
            'requestBody' => ['$ref' => '#/components/requestBodies/Pet'],
        ]);
        $definition = new OperationDefinition('/pets', 'post', $operation, []);

        self::assertNull($definition->requestBody());
    }
}
