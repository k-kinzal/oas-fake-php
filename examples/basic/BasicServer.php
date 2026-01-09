<?php

declare(strict_types=1);

namespace OasFake\Examples\Basic;

use OasFake\Server;

final class BasicServer extends Server
{
    protected static string $SCHEMA = __DIR__ . '/openapi.yaml';
    protected static string $CASSETTE_PATH = __DIR__ . '/cassettes';
}
