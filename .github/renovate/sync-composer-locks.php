<?php

/**
 * Regenerates the per-PHP Composer lock files after Renovate edits composer.json.
 *
 * Renovate only understands a single composer.lock, but this repository commits one
 * lock file per supported PHP version (composer.lock.php-*). This script replays the
 * composer.json change against every lock file, reusing each lock's own platform
 * override and prefer-lowest setting, and only updates the packages whose constraints
 * changed (plus their dependencies).
 *
 * The Renovate cooldown (minimumReleaseAge) is enforced for every package that ends up
 * with a new locked version, including transitive dependencies, by excluding versions
 * that are too recent and resolving again.
 *
 * Usage: php .github/renovate/sync-composer-locks.php <base-git-ref>
 */

declare(strict_types=1);

const MAX_RESOLUTION_ATTEMPTS = 20;

function fail(string $message): never
{
    fwrite(STDERR, "error: {$message}\n");
    exit(1);
}

/**
 * @return array<string, mixed>
 */
function decodeJson(string $json, string $source): array
{
    try {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        fail("{$source} is not valid JSON: {$e->getMessage()}");
    }
    if (!is_array($data)) {
        fail("{$source} must contain a JSON object");
    }

    return $data;
}

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $path): array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        fail("cannot read {$path}");
    }

    return decodeJson($contents, $path);
}

function cooldownDays(string $renovateConfigPath): int
{
    $age = readJsonFile($renovateConfigPath)['minimumReleaseAge'] ?? null;
    if (!is_string($age) || preg_match('/^(\d+) days?$/', $age, $matches) !== 1) {
        fail("{$renovateConfigPath} must define minimumReleaseAge as \"<N> days\"");
    }

    return (int) $matches[1];
}

/**
 * @return list<string>
 */
function changedPackages(string $baseRef): array
{
    $baseJson = shell_exec('git show ' . escapeshellarg("{$baseRef}:composer.json"));
    if (!is_string($baseJson) || $baseJson === '') {
        fail("cannot read composer.json at {$baseRef}");
    }
    $base = decodeJson($baseJson, "composer.json at {$baseRef}");
    $head = readJsonFile('composer.json');

    $changed = [];
    foreach (['require', 'require-dev'] as $section) {
        $before = $base[$section] ?? [];
        $after = $head[$section] ?? [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $name) {
            if (($before[$name] ?? null) !== ($after[$name] ?? null) && str_contains($name, '/')) {
                $changed[$name] = true;
            }
        }
    }

    return array_keys($changed);
}

/**
 * @param array<string, mixed> $lock
 *
 * @return array<string, array{version: string, time: ?string}>
 */
function lockedVersions(array $lock): array
{
    $versions = [];
    foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        $versions[$package['name']] = [
            'version' => $package['version'],
            'time' => $package['time'] ?? null,
        ];
    }

    return $versions;
}

function isDevVersion(string $version): bool
{
    return str_starts_with($version, 'dev-') || str_ends_with($version, '-dev');
}

/**
 * @param array<string, mixed> $originalLock
 * @param array<string, mixed> $updatedLock
 *
 * @return array<string, string> package name => version that is newer than the cooldown allows
 */
function versionsInsideCooldown(array $originalLock, array $updatedLock, DateTimeImmutable $cutoff): array
{
    $before = lockedVersions($originalLock);
    $tooNew = [];
    foreach (lockedVersions($updatedLock) as $name => $package) {
        if (($before[$name]['version'] ?? null) === $package['version'] || isDevVersion($package['version'])) {
            continue;
        }
        if ($package['time'] === null) {
            fail("{$name} {$package['version']} has no release time, so the cooldown cannot be verified");
        }
        if (new DateTimeImmutable($package['time']) > $cutoff) {
            $tooNew[$name] = $package['version'];
        }
    }

    return $tooNew;
}

/**
 * @param list<string>          $packages
 * @param array<string, string> $upperBounds package name => first excluded version
 * @param array<string, mixed>  $lock
 */
function runComposerUpdate(array $packages, array $upperBounds, array $lock, string $cacheDir): void
{
    $platform = $lock['platform-overrides'] ?? null;
    if (!is_array($platform) || $platform === []) {
        fail('every composer.lock.php-* file must record the platform override it was generated with');
    }

    // The platform override lives in a throwaway global config so that it is written to
    // platform-overrides without changing composer.json or the lock's content-hash.
    $home = sys_get_temp_dir() . '/sync-composer-locks-home-' . getmypid();
    if (!is_dir($home) && !mkdir($home, 0700, true)) {
        fail("cannot create {$home}");
    }
    file_put_contents($home . '/config.json', json_encode(['config' => ['platform' => $platform]], JSON_THROW_ON_ERROR));
    // Keep repository credentials (e.g. a GitHub token) from the real Composer home.
    $originalHome = getenv('COMPOSER_HOME') ?: trim((string) shell_exec('composer config --global home 2>/dev/null'));
    if ($originalHome !== '' && is_file($originalHome . '/auth.json')) {
        copy($originalHome . '/auth.json', $home . '/auth.json');
    }

    $command = ['composer', 'update', '--no-install', '--no-scripts', '--no-plugins', '--no-audit', '--no-progress', '--with-all-dependencies'];
    if (($lock['prefer-lowest'] ?? false) === true) {
        $command[] = '--prefer-lowest';
    }
    foreach ($upperBounds as $name => $version) {
        $command[] = '--with=' . $name . ':<' . $version;
    }
    array_push($command, ...$packages);

    $env = getenv();
    $env['COMPOSER_HOME'] = $home;
    $env['COMPOSER_CACHE_DIR'] = $cacheDir;
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, null, $env);
    if (!is_resource($process)) {
        fail('cannot start composer');
    }
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        fail("composer update failed with exit code {$exitCode}");
    }
}

/**
 * @param list<string> $packages
 */
function syncLockFile(string $lockPath, array $packages, DateTimeImmutable $cutoff, string $cacheDir): void
{
    $originalContents = file_get_contents($lockPath);
    if ($originalContents === false) {
        fail("cannot read {$lockPath}");
    }
    $originalLock = decodeJson($originalContents, $lockPath);

    $upperBounds = [];
    for ($attempt = 1; $attempt <= MAX_RESOLUTION_ATTEMPTS; ++$attempt) {
        file_put_contents('composer.lock', $originalContents);
        runComposerUpdate($packages, $upperBounds, $originalLock, $cacheDir);
        $updatedLock = readJsonFile('composer.lock');

        $tooNew = versionsInsideCooldown($originalLock, $updatedLock, $cutoff);
        if ($tooNew === []) {
            if (!copy('composer.lock', $lockPath)) {
                fail("cannot write {$lockPath}");
            }
            echo "Synced {$lockPath}\n";

            return;
        }

        foreach ($tooNew as $name => $version) {
            echo "{$lockPath}: {$name} {$version} is still inside the cooldown, excluding it\n";
            $upperBounds[$name] = $version;
        }
    }

    fail("{$lockPath}: no resolution outside the cooldown after " . MAX_RESOLUTION_ATTEMPTS . ' attempts');
}

$baseRef = $argv[1] ?? fail('usage: php .github/renovate/sync-composer-locks.php <base-git-ref>');

$packages = changedPackages($baseRef);
if ($packages === []) {
    echo "composer.json dependencies are unchanged, nothing to sync\n";
    exit(0);
}
echo 'Changed packages: ' . implode(', ', $packages) . "\n";

$days = cooldownDays('renovate.json');
$cutoff = new DateTimeImmutable("-{$days} days");
$cacheDir = getenv('COMPOSER_CACHE_DIR') ?: trim((string) shell_exec('composer config --global cache-dir 2>/dev/null'));
if ($cacheDir === '') {
    $cacheDir = sys_get_temp_dir() . '/sync-composer-locks-cache';
}

$lockFiles = glob('composer.lock.php-*') ?: [];
if ($lockFiles === []) {
    fail('no composer.lock.php-* files found');
}

$rootLockBackup = is_file('composer.lock') ? file_get_contents('composer.lock') : null;
try {
    foreach ($lockFiles as $lockPath) {
        syncLockFile($lockPath, $packages, $cutoff, $cacheDir);
    }
} finally {
    if ($rootLockBackup === null) {
        @unlink('composer.lock');
    } else {
        file_put_contents('composer.lock', $rootLockBackup);
    }
}
