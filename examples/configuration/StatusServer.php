<?php

declare(strict_types=1);

namespace OasFake\Examples\Configuration;

use OasFake\Server;

final class StatusServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
    protected static string $MODE = 'fake';
    protected static bool $VALIDATE_REQUESTS = true;
    protected static bool $VALIDATE_RESPONSES = true;
}
