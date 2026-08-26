<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use OasFake\OperationInfo;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class OperationInfoTest extends TestCase
{
    /**
     * @throws \cebe\openapi\exceptions\TypeErrorException when operation metadata is invalid
     */
    public function testStoresOperationMetadata(): void
    {
        $operation = new Operation(['responses' => []]);
        $parameter = new Parameter(['name' => 'petId', 'in' => 'path']);

        $info = new OperationInfo(
            pathPattern: '/pets/{petId}',
            method: 'get',
            operationId: 'getPetById',
            operation: $operation,
            parameters: [$parameter],
        );

        self::assertSame('/pets/{petId}', $info->pathPattern);
        self::assertSame('get', $info->method);
        self::assertSame('getPetById', $info->operationId);
        self::assertSame($operation, $info->operation);
        self::assertSame([$parameter], $info->parameters);
        self::assertSame(['/'], $info->serverUrls);
    }
}
