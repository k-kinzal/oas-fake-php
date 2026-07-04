<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Mode;
use OasFake\Schema;
use OasFake\ServerOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerOptions::class)]
final class ServerOptionsTest extends TestCase
{
    public function testConstructorStoresValues(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $options = new ServerOptions(
            schema: $schema,
            mode: Mode::FAKE,
            cassettePath: '/tmp/cassettes',
            validateRequests: true,
            validateResponses: false,
            fakerOptions: ['alwaysFakeOptionals' => true],
            middleware: [],
        );

        self::assertSame($schema, $options->schema);
        self::assertSame(Mode::FAKE, $options->mode->value());
        self::assertSame('/tmp/cassettes', $options->cassettePath);
        self::assertTrue($options->validateRequests);
        self::assertFalse($options->validateResponses);
        self::assertSame(['alwaysFakeOptionals' => true], $options->fakerOptions);
        self::assertSame([], $options->middleware);
    }

    public function testConstructorKeepsModeInstance(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $mode = Mode::fromString('replay');

        $options = new ServerOptions(
            schema: $schema,
            mode: $mode,
            cassettePath: '/tmp/cassettes',
            validateRequests: true,
            validateResponses: true,
            fakerOptions: [],
            middleware: [],
        );

        self::assertSame($mode, $options->mode);
    }
}
