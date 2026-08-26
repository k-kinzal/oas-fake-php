<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use LogicException;
use OasFake\Exception\ReplayMismatchError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;

/**
 * @covers \OasFake\Exception\ReplayMismatchError
 */
#[CoversClass(ReplayMismatchError::class)]
final class ReplayMismatchErrorTest extends TestCase
{
    public function testForRequestRetainsOriginalLookupFailure(): void
    {
        $request = new VcrRequest('GET', 'https://example.com/pets', []);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertSame($previous, $error->getPrevious());
    }

    public function testMessageContainsMethodAndUrl(): void
    {
        $request = new VcrRequest('POST', 'https://example.com/pets', []);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertStringContainsString('POST', $error->getMessage());
        self::assertStringContainsString('https://example.com/pets', $error->getMessage());
    }

    public function testMessageContainsBodyWhenPresent(): void
    {
        $request = new VcrRequest('POST', 'https://example.com/pets', []);
        $request->setBody('{"name":"Buddy"}');
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertStringContainsString('Request body: {"name":"Buddy"}', $error->getMessage());
    }

    public function testMessageTruncatesLongBody(): void
    {
        $request = new VcrRequest('POST', 'https://example.com/pets', []);
        $longBody = 'b' . str_repeat('a', 249);
        $request->setBody($longBody);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertStringContainsString('Request body: b' . str_repeat('a', 199) . '...', $error->getMessage());
        self::assertSame(0, $error->getCode());
    }

    public function testMessageDoesNotTruncateABodyAtTheBoundary(): void
    {
        $request = new VcrRequest('POST', 'https://example.com/pets', []);
        $request->setBody(str_repeat('a', 200));

        $error = ReplayMismatchError::forRequest($request, new LogicException('No matching recording'));

        self::assertStringContainsString('Request body: ' . str_repeat('a', 200), $error->getMessage());
        self::assertStringNotContainsString('...', $error->getMessage());
    }

    public function testMessageDoesNotContainBodyWhenEmpty(): void
    {
        $request = new VcrRequest('GET', 'https://example.com/pets', []);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertStringNotContainsString('Request body:', $error->getMessage());
    }

    public function testGetPreviousReturnsOriginalException(): void
    {
        $request = new VcrRequest('GET', 'https://example.com/pets', []);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertSame($previous, $error->getPrevious());
        self::assertSame(0, $error->getCode());
    }
}
