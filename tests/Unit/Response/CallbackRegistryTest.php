<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Response;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OpenAPIValidation\PSR7\OperationAddress;
use OasFakePHP\Response\CallbackRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

#[CoversClass(CallbackRegistry::class)]
final class CallbackRegistryTest extends TestCase
{
    private CallbackRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CallbackRegistry();
    }

    public function testRegisterAndHasForOperationId(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        $this->registry->register('getPetById', $callback);

        self::assertTrue($this->registry->hasForOperationId('getPetById'));
        self::assertFalse($this->registry->hasForOperationId('listPets'));
    }

    public function testRegisterForPathAndHas(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        $this->registry->registerForPath('/pets', 'GET', $callback);

        $operation = new OperationAddress('/pets', 'get');
        self::assertTrue($this->registry->has($operation));
    }

    public function testHasIsCaseInsensitive(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        $this->registry->registerForPath('/pets', 'get', $callback);

        $operation = new OperationAddress('/pets', 'GET');
        self::assertTrue($this->registry->has($operation));
    }

    public function testExecuteWithOperationIdCallback(): void
    {
        $expectedResponse = new Response(200, [], 'custom');
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => $expectedResponse;

        $this->registry->register('getPetById', $callback);

        $request = new ServerRequest('GET', 'https://example.com/pets/1');
        $operation = new OperationAddress('/pets/{petId}', 'get');

        $response = $this->registry->execute($operation, $request, null, 'getPetById');

        self::assertSame($expectedResponse, $response);
    }

    public function testExecuteWithPathCallback(): void
    {
        $expectedResponse = new Response(200, [], 'path callback');
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => $expectedResponse;

        $this->registry->registerForPath('/pets', 'GET', $callback);

        $request = new ServerRequest('GET', 'https://example.com/pets');
        $operation = new OperationAddress('/pets', 'get');

        $response = $this->registry->execute($operation, $request, null);

        self::assertSame($expectedResponse, $response);
    }

    public function testExecuteReturnsDefaultResponseWhenNoCallback(): void
    {
        $defaultResponse = new Response(200, [], 'default');
        $request = new ServerRequest('GET', 'https://example.com/pets');
        $operation = new OperationAddress('/pets', 'get');

        $response = $this->registry->execute($operation, $request, $defaultResponse);

        self::assertSame($defaultResponse, $response);
    }

    public function testExecuteThrowsWhenNoCallbackAndNoDefault(): void
    {
        $request = new ServerRequest('GET', 'https://example.com/pets');
        $operation = new OperationAddress('/pets', 'get');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No callback registered');

        $this->registry->execute($operation, $request, null);
    }

    public function testCallbackReceivesRequestAndDefaultResponse(): void
    {
        $defaultResponse = new Response(200, [], 'default');
        $receivedRequest = null;
        $receivedDefault = null;

        $callback = static function (ServerRequestInterface $req, ?ResponseInterface $res) use (&$receivedRequest, &$receivedDefault): ResponseInterface {
            $receivedRequest = $req;
            $receivedDefault = $res;

            return new Response(201, [], 'modified');
        };

        $this->registry->register('testOp', $callback);

        $request = new ServerRequest('POST', 'https://example.com/test');
        $operation = new OperationAddress('/test', 'post');

        $this->registry->execute($operation, $request, $defaultResponse, 'testOp');

        self::assertSame($request, $receivedRequest);
        self::assertSame($defaultResponse, $receivedDefault);
    }

    public function testClear(): void
    {
        $callback = static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => new Response();

        $this->registry->register('op1', $callback);
        $this->registry->registerForPath('/test', 'GET', $callback);

        self::assertTrue($this->registry->hasForOperationId('op1'));

        $this->registry->clear();

        self::assertFalse($this->registry->hasForOperationId('op1'));
        self::assertFalse($this->registry->has(new OperationAddress('/test', 'get')));
    }
}
