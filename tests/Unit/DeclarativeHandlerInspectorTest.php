<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use OasFake\DeclarativeHandlerInspector;
use OasFake\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

#[CoversClass(DeclarativeHandlerInspector::class)]
final class DeclarativeHandlerInspectorTest extends TestCase
{
    public function testIsCandidateRequiresTheRequestHandlerSignature(): void
    {
        $inspector = new DeclarativeHandlerInspector();

        self::assertTrue($inspector->isCandidate(new ReflectionMethod(InspectorServerFixture::class, 'listPets')));
        self::assertFalse($inspector->isCandidate(new ReflectionMethod(InspectorServerFixture::class, 'invalid')));
    }

    public function testRouteReturnsDeclaredMapping(): void
    {
        $route = (new DeclarativeHandlerInspector())->route(new ReflectionMethod(InspectorServerFixture::class, 'listPets'));

        self::assertNotNull($route);
        self::assertSame('GET', $route->method);
        self::assertSame('/pets', $route->path);
    }
}

final class InspectorServerFixture
{
    #[Route(method: 'GET', path: '/pets')]
    public function listPets(ServerRequestInterface $request, ?ResponseInterface $response): ResponseInterface
    {
        return $response ?? new \GuzzleHttp\Psr7\Response(200);
    }

    public function invalid(): string
    {
        return 'invalid';
    }
}
