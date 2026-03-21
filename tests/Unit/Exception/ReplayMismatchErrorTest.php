<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit\Exception;

use AssertionError;
use LogicException;
use OasFake\Exception\ReplayMismatchError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request as VcrRequest;

#[CoversClass(ReplayMismatchError::class)]
final class ReplayMismatchErrorTest extends TestCase
{
    public function testForRequestReturnsAssertionError(): void
    {
        $request = new VcrRequest('GET', 'https://example.com/pets', []);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertInstanceOf(AssertionError::class, $error);
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
        $longBody = str_repeat('a', 250);
        $request->setBody($longBody);
        $previous = new LogicException('No matching recording');

        $error = ReplayMismatchError::forRequest($request, $previous);

        self::assertStringContainsString('Request body: ' . str_repeat('a', 200) . '...', $error->getMessage());
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
    }
}
