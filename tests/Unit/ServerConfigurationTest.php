<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\Mode;
use OasFake\ServerConfiguration;
use OasFake\Testing\Petstore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(ServerConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\CassetteNameNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\EnvironmentResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Mode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\OasFake\Schema::class)]
final class ServerConfigurationTest extends TestCase
{
    public function testSetSchemaOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setSchema(Petstore::path());

        self::assertSame('Petstore API', $configuration->schema('')->openApi()->info->title);
    }

    public function testSchemaUsesDefaultPath(): void
    {
        self::assertSame('Petstore API', (new ServerConfiguration())->schema(Petstore::path())->openApi()->info->title);
    }

    public function testSetModeOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setMode(Mode::RECORD);
        putenv('OAS_FAKE_MODE');

        try {
            self::assertSame(Mode::RECORD, $configuration->mode(Mode::FAKE)->value());
        } finally {
            putenv('OAS_FAKE_MODE=fake');
        }
    }

    public function testModeUsesDefault(): void
    {
        putenv('OAS_FAKE_MODE');

        try {
            self::assertSame(Mode::REPLAY, (new ServerConfiguration())->mode(Mode::REPLAY)->value());
        } finally {
            putenv('OAS_FAKE_MODE=fake');
        }
    }

    public function testSetCassettePathOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setCassettePath('/configured');
        putenv('OAS_FAKE_CASSETTE_PATH');

        try {
            self::assertSame('/configured', $configuration->cassettePath('/default'));
        } finally {
            putenv('OAS_FAKE_CASSETTE_PATH');
        }
    }

    public function testCassettePathUsesDefault(): void
    {
        putenv('OAS_FAKE_CASSETTE_PATH');

        self::assertSame('/default', (new ServerConfiguration())->cassettePath('/default'));
    }

    public function testSetCassetteNameOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setCassetteName('My Cassette');
        putenv('OAS_FAKE_CASSETTE_NAME');

        self::assertSame('my-cassette', $configuration->cassetteName('', \OasFake\Server::class));
    }

    public function testCassetteNameFallsBackToNormalizedServerClass(): void
    {
        putenv('OAS_FAKE_CASSETTE_NAME');

        self::assertSame(
            'oasfake-server',
            (new ServerConfiguration())->cassetteName('', \OasFake\Server::class),
        );
    }

    public function testSetRequestValidationOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setRequestValidation(false);
        putenv('OAS_FAKE_VALIDATE_REQUESTS');

        self::assertFalse($configuration->requestValidation(true));
    }

    public function testRequestValidationUsesDefault(): void
    {
        putenv('OAS_FAKE_VALIDATE_REQUESTS');

        self::assertTrue((new ServerConfiguration())->requestValidation(true));
    }

    public function testSetResponseValidationOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setResponseValidation(false);
        putenv('OAS_FAKE_VALIDATE_RESPONSES');

        self::assertFalse($configuration->responseValidation(true));
    }

    public function testResponseValidationUsesDefault(): void
    {
        putenv('OAS_FAKE_VALIDATE_RESPONSES');

        self::assertTrue((new ServerConfiguration())->responseValidation(true));
    }

    public function testSetFakerOptionsOverridesDefault(): void
    {
        $configuration = new ServerConfiguration();
        $configuration->setFakerOptions(['minItems' => 2]);

        self::assertSame(['minItems' => 2], $configuration->fakerOptions([]));
    }

    public function testFakerOptionsUsesDefault(): void
    {
        self::assertSame(['maxItems' => 3], (new ServerConfiguration())->fakerOptions(['maxItems' => 3]));
    }

    public function testAddMiddlewareAppendsAfterDefaults(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };
        $configuration = new ServerConfiguration();
        $configuration->addMiddleware($middleware);

        self::assertSame([$middleware], $configuration->middleware([]));
    }

    public function testMiddlewarePreservesDefaultOrder(): void
    {
        $first = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };
        $second = clone $first;
        $configuration = new ServerConfiguration();
        $configuration->addMiddleware($second);

        self::assertSame([$first, $second], $configuration->middleware([$first]));
    }
}
