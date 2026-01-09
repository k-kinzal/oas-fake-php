<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use OasFake\Exception\ValidationException;
use OasFake\Handler;
use OasFake\HandlerMap;
use OasFake\Interceptor;
use OasFake\Mode;
use OasFake\Schema;
use OasFake\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use VCR\Request as VcrRequest;

#[CoversClass(Interceptor::class)]
final class InterceptorTest extends TestCase
{
    private Schema $schema;

    private Validator $validator;

    private HandlerMap $handlers;

    private string $cassettePath;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/../Fixtures/openapi/petstore.yaml');
        $this->validator = new Validator($this->schema);
        $this->handlers = new HandlerMap();
        $this->cassettePath = sys_get_temp_dir() . '/oas-fake-test-cassettes';

        if (!is_dir($this->cassettePath)) {
            mkdir($this->cassettePath, 0777, true);
        }
    }

    public function testIsRunningReturnsFalseByDefault(): void
    {
        $interceptor = $this->createInterceptor();

        self::assertFalse($interceptor->isRunning());
    }

    public function testHandleReturnsFakeResponseForValidRequest(): void
    {
        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody() ?? '', true);
        self::assertIsArray($body);
    }

    public function testHandleReturnsFakeResponseForSingleResource(): void
    {
        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets/1', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody() ?? '', true);
        self::assertIsArray($body);
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('name', $body);
    }

    public function testHandleUsesStubOverFaker(): void
    {
        $stubBody = json_encode([['id' => 42, 'name' => 'Stubbed Pet']], JSON_THROW_ON_ERROR);
        $this->handlers->forOperation('listPets', Handler::response(200, $stubBody));

        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($stubBody, $vcrResponse->getBody());
    }

    public function testHandleUsesPathStubOverFaker(): void
    {
        $stubBody = json_encode([['id' => 77, 'name' => 'Path Stub']], JSON_THROW_ON_ERROR);
        $this->handlers->forPath('/pets', 'GET', Handler::response(200, $stubBody));

        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($stubBody, $vcrResponse->getBody());
    }

    public function testHandleValidatesRequestWhenEnabled(): void
    {
        $interceptor = $this->createInterceptor(validateRequests: true, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/nonexistent', []);

        $this->expectException(ValidationException::class);

        $interceptor->handle($vcrRequest);
    }

    public function testHandleReturns500WhenOperationCannotBeResolved(): void
    {
        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/nonexistent', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(500, $vcrResponse->getStatusCode());
    }

    public function testHandleExecutesMiddleware(): void
    {
        $middleware = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Middleware', 'applied');
            }
        };

        $interceptor = $this->createInterceptor(
            validateRequests: false,
            validateResponses: false,
            middleware: [$middleware],
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $headers = $vcrResponse->getHeaders();
        self::assertArrayHasKey('X-Middleware', $headers);
        self::assertSame('applied', $headers['X-Middleware']);
    }

    public function testHandleExecutesMiddlewareInCorrectOrder(): void
    {
        $first = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', ($response->getHeaderLine('X-Order')) . 'first');
            }
        };

        $second = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);

                return $response->withHeader('X-Order', ($response->getHeaderLine('X-Order')) . 'second');
            }
        };

        $interceptor = $this->createInterceptor(
            validateRequests: false,
            validateResponses: false,
            middleware: [$first, $second],
        );
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        $headers = $vcrResponse->getHeaders();
        self::assertSame('secondfirst', $headers['X-Order']);
    }

    public function testHandleWithCallbackStub(): void
    {
        $callbackBody = json_encode([['id' => 1, 'name' => 'Callback Pet']], JSON_THROW_ON_ERROR);
        $this->handlers->forOperation('listPets', Handler::callback(
            static fn (ServerRequestInterface $request, ?ResponseInterface $default): ResponseInterface => new Response(200, ['Content-Type' => 'application/json'], $callbackBody),
        ));

        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        self::assertSame($callbackBody, $vcrResponse->getBody());
    }

    public function testHandleWithStatusStub(): void
    {
        $this->handlers->forOperation('listPets', Handler::status(201));

        $interceptor = $this->createInterceptor(validateRequests: false, validateResponses: false);
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $interceptor->handle($vcrRequest);

        self::assertSame(201, $vcrResponse->getStatusCode());
    }

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    private function createInterceptor(
        string $mode = Mode::FAKE,
        bool $validateRequests = true,
        bool $validateResponses = true,
        array $middleware = [],
    ): Interceptor {
        return new Interceptor(
            mode: $mode,
            cassettePath: $this->cassettePath,
            schema: $this->schema,
            validator: $this->validator,
            fakerOptions: [],
            handlers: $this->handlers,
            validateRequests: $validateRequests,
            validateResponses: $validateResponses,
            middleware: $middleware,
        );
    }
}
