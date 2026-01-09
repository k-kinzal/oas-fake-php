<?php

declare(strict_types=1);

namespace OasFake\Examples\CustomResponses;

use OasFake\Server;

final class ECommerceServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static bool $VALIDATE_RESPONSES = false;
}
