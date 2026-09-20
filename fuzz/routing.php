<?php

declare(strict_types=1);

use OasFake\OperationLookup;
use OasFake\Schema;
use PhpFuzzer\Config;

/** @var Config $config */
require __DIR__ . '/../vendor/autoload.php';

$lookup = new OperationLookup(Schema::fromString(
    '{"openapi":"3.0.0","info":{"title":"Routing contract","version":"1"},"paths":{"/pets/{id}":{"get":{"operationId":"pet","responses":{"200":{"description":"ok"}}}},"/pets/search":{"get":{"operationId":"search","responses":{"200":{"description":"ok"}}}},"/health":{"get":{"operationId":"health","responses":{"200":{"description":"ok"}}}}}}',
    'json',
));

$config->setMaxLen(256);
$config->setAllowedExceptions([]);
$config->addDictionary(__DIR__ . '/routing.dict');
$config->setTarget(static function (string $input) use ($lookup): void {
    $path = $input;
    $segments = explode('/', $path);
    $expected = null;
    if ($path === '/health') {
        $expected = 'health';
    } elseif ($path === '/pets/search') {
        $expected = 'search';
    } elseif (count($segments) === 3 && $segments[0] === '' && $segments[1] === 'pets' && $segments[2] !== '') {
        $expected = 'pet';
    }

    $actual = $lookup->findByRequestPathAndMethod($path, 'GET')?->operationId();
    $wrongMethod = $lookup->findByRequestPathAndMethod($path, 'POST');
    if ($actual !== $expected || $wrongMethod !== null) {
        throw new Error(sprintf(
            'routing contract: path(hex)=%s expected=%s actual=%s POST=%s PHP=%s',
            bin2hex($input),
            $expected ?? 'none',
            $actual ?? 'none',
            $wrongMethod === null ? 'none' : $wrongMethod->operationId(),
            PHP_VERSION,
        ));
    }
});
