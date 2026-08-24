<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFake\OperationRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperationRequest::class)]
final class OperationRequestTest extends TestCase
{
    public function testCarriesResolvedRequestContract(): void
    {
        $address = new OperationAddress('/pets', 'get');
        $request = new OperationRequest('/pets', 'GET', null, $address);

        self::assertSame('/pets', $request->path);
        self::assertSame('GET', $request->method);
        self::assertNull($request->definition);
        self::assertSame($address, $request->address);
    }
}
