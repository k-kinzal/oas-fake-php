<?php

declare(strict_types=1);

namespace OasFakePHP\Tests\Unit\Vcr;

use GuzzleHttp\Psr7\Response;
use OasFakePHP\Config\Configuration;
use OasFakePHP\Response\CallbackRegistry;
use OasFakePHP\Vcr\FakeRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;

#[CoversClass(FakeRequestHandler::class)]
final class FakeRequestHandlerTest extends TestCase
{
    private Configuration $config;
    private CallbackRegistry $callbackRegistry;
    private FakeRequestHandler $handler;

    protected function setUp(): void
    {
        $fixturesPath = __DIR__ . '/../../Fixtures/openapi';
        $this->config = new Configuration();
        $this->config->fromYamlFile($fixturesPath . '/petstore.yaml');
        $this->callbackRegistry = new CallbackRegistry();
        $this->handler = new FakeRequestHandler($this->config, $this->callbackRegistry);
    }

    public function testHandleRequestReturnsFakeResponse(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertInstanceOf(VcrResponse::class, $vcrResponse);
        self::assertSame(200, $vcrResponse->getStatusCode());
    }

    public function testHandleRequestWithPathParameter(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets/123', []);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
        $body = json_decode($vcrResponse->getBody() ?? '', true);
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('name', $body);
    }

    public function testHandleRequestUsesOperationIdCallback(): void
    {
        // Response must match schema: array of pets
        $customBody = json_encode([['id' => 99, 'name' => 'Custom Pet']], JSON_THROW_ON_ERROR);
        $customResponse = new Response(200, ['Content-Type' => 'application/json'], $customBody);
        $this->callbackRegistry->register(
            'listPets',
            static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => $customResponse,
        );

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertSame($customBody, $vcrResponse->getBody());
    }

    public function testHandleRequestUsesPathCallback(): void
    {
        // Response must match schema: array of pets
        $customBody = json_encode([['id' => 88, 'name' => 'Path Pet']], JSON_THROW_ON_ERROR);
        $customResponse = new Response(200, ['Content-Type' => 'application/json'], $customBody);
        $this->callbackRegistry->registerForPath(
            '/pets',
            'GET',
            static fn (ServerRequestInterface $req, ?ResponseInterface $res): ResponseInterface => $customResponse,
        );

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertSame($customBody, $vcrResponse->getBody());
    }

    public function testValidateRequest(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);

        // Should not throw
        $this->handler->validateRequest($vcrRequest);
        self::assertTrue(true);
    }

    public function testValidateRequestSkipsWhenDisabled(): void
    {
        $this->config->enableRequestValidation(false);

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/invalid', []);

        // Should not throw even with invalid path
        $this->handler->validateRequest($vcrRequest);
        self::assertTrue(true);
    }

    public function testValidateResponse(): void
    {
        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $vcrResponse = new VcrResponse(['code' => 200, 'message' => 'OK'], [
            'Content-Type' => 'application/json',
        ], json_encode([['id' => 1, 'name' => 'Test']], JSON_THROW_ON_ERROR));

        // Should not throw
        $this->handler->validateResponse($vcrRequest, $vcrResponse);
        self::assertTrue(true);
    }

    public function testValidateResponseSkipsWhenDisabled(): void
    {
        $this->config->enableResponseValidation(false);

        $vcrRequest = new VcrRequest('GET', 'https://api.petstore.example.com/pets', []);
        $vcrResponse = new VcrResponse(['code' => 200, 'message' => 'OK'], [], '{"invalid": "data"}');

        // Should not throw even with invalid response
        $this->handler->validateResponse($vcrRequest, $vcrResponse);
        self::assertTrue(true);
    }

    public function testHandleRequestWithPutMethod(): void
    {
        $this->config->enableResponseValidation(false);
        $body = json_encode(['name' => 'Updated Pet'], JSON_THROW_ON_ERROR);
        $vcrRequest = new VcrRequest(
            'PUT',
            'https://api.petstore.example.com/pets/123',
            ['Content-Type' => 'application/json'],
        );
        $vcrRequest->setBody($body);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
    }

    public function testHandleRequestWithPatchMethod(): void
    {
        $this->config->enableResponseValidation(false);
        $body = json_encode(['name' => 'Patched'], JSON_THROW_ON_ERROR);
        $vcrRequest = new VcrRequest(
            'PATCH',
            'https://api.petstore.example.com/pets/123',
            ['Content-Type' => 'application/json'],
        );
        $vcrRequest->setBody($body);

        $vcrResponse = $this->handler->handleRequest($vcrRequest);

        self::assertSame(200, $vcrResponse->getStatusCode());
    }
}
