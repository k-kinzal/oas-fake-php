<?php

declare(strict_types=1);

namespace OasFake;

use OasFake\Exception\SchemaNotFoundException;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Owns mutable server overrides and resolves them against defaults and environment policy.
 *
 * @visibility namespace
 */
final class ServerConfiguration
{
    private ?string $schema = null;
    private ?Mode $mode = null;
    private ?string $cassettePath = null;
    private ?string $cassetteName = null;
    private ?bool $validateRequests = null;
    private ?bool $validateResponses = null;

    /** @var array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}|null */
    private ?array $fakerOptions = null;

    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    /**
     * Store a schema-path override.
     */
    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * Store a normalized server-mode override.
     */
    public function setMode(string|Mode $mode): void
    {
        $this->mode = Mode::from($mode);
    }

    /**
     * Store a cassette-directory override.
     */
    public function setCassettePath(string $path): void
    {
        $this->cassettePath = $path;
    }

    /**
     * Store a cassette-name override.
     */
    public function setCassetteName(string $name): void
    {
        $this->cassetteName = $name;
    }

    /**
     * Store the request-validation policy.
     */
    public function setRequestValidation(bool $enabled): void
    {
        $this->validateRequests = $enabled;
    }

    /**
     * Store the response-validation policy.
     */
    public function setResponseValidation(bool $enabled): void
    {
        $this->validateResponses = $enabled;
    }

    /**
     * Store the OpenAPI faker policy.
     *
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $options
     */
    public function setFakerOptions(array $options): void
    {
        $this->fakerOptions = $options;
    }

    /**
     * Append middleware after subclass-declared middleware.
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * @throws SchemaNotFoundException when neither an override nor a default is configured
     */
    public function schema(string $default): Schema
    {
        $path = $this->schema ?? $default;
        if ($path === '') {
            throw new SchemaNotFoundException('No schema configured. Set $SCHEMA or call withSchema().');
        }

        return Schema::fromFile($path);
    }

    /**
     * Resolve mode with environment precedence.
     */
    public function mode(string $default): Mode
    {
        $configured = ($this->mode ?? Mode::fromString($default))->value();

        return Mode::fromString((new EnvironmentResolver())->string('OAS_FAKE_MODE', $configured));
    }

    /**
     * Resolve cassette directory with environment precedence.
     */
    public function cassettePath(string $default): string
    {
        return (new EnvironmentResolver())->string('OAS_FAKE_CASSETTE_PATH', $this->cassettePath ?? $default);
    }

    /**
     * Resolve and normalize cassette name with environment precedence.
     */
    public function cassetteName(string $default, string $serverClass): string
    {
        $fallback = $this->cassetteName ?? ($default !== '' ? $default : $serverClass);
        $name = (new EnvironmentResolver())->string('OAS_FAKE_CASSETTE_NAME', $fallback);

        return (new CassetteNameNormalizer())->normalize($name);
    }

    /**
     * Resolve request-validation policy with environment precedence.
     */
    public function requestValidation(bool $default): bool
    {
        return (new EnvironmentResolver())->boolean('OAS_FAKE_VALIDATE_REQUESTS', $this->validateRequests ?? $default);
    }

    /**
     * Resolve response-validation policy with environment precedence.
     */
    public function responseValidation(bool $default): bool
    {
        return (new EnvironmentResolver())->boolean('OAS_FAKE_VALIDATE_RESPONSES', $this->validateResponses ?? $default);
    }

    /**
     * @param array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int} $default
     *
     * @return array{alwaysFakeOptionals?: bool, minItems?: int, maxItems?: int}
     */
    public function fakerOptions(array $default): array
    {
        return $this->fakerOptions ?? $default;
    }

    /**
     * @param list<MiddlewareInterface> $default
     *
     * @return list<MiddlewareInterface>
     */
    public function middleware(array $default): array
    {
        return array_merge($default, $this->middleware);
    }
}
