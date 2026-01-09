<?php

declare(strict_types=1);

namespace OasFake\Examples\FakerCustomization;

use OasFake\Server;

final class BlogServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static bool $VALIDATE_RESPONSES = false;
}
