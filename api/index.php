<?php

declare(strict_types=1);

/**
 * Vercel Function entry point for the isolated, non-production DESATARA demo.
 *
 * Intentionally fail closed before loading Laravel or contacting a database.
 * This does NOT provision or migrate PostgreSQL.
 */
$databaseUrl = getenv('DB_URL');
$databaseName = getenv('DB_DATABASE');
if (is_string($databaseUrl) && $databaseUrl !== '') {
    $path = parse_url($databaseUrl, PHP_URL_PATH);
    $databaseName = is_string($path) ? ltrim($path, '/') : null;
}

$appKey = getenv('APP_KEY');
$ready = getenv('APP_ENV') === 'demo'
    && getenv('DESATARA_DEMO_ENABLED') === 'true'
    && getenv('APP_DEBUG') === 'false'
    && getenv('DB_CONNECTION') === 'pgsql'
    && $databaseName === 'desatara_demo'
    && getenv('SESSION_DRIVER') === 'database'
    && getenv('CACHE_STORE') === 'database'
    && getenv('LOG_CHANNEL') === 'stderr'
    && is_string($appKey)
    && str_starts_with($appKey, 'base64:')
    && strlen($appKey) >= 50
    && (is_string($databaseUrl) && $databaseUrl !== '' || is_string(getenv('DB_HOST')) && getenv('DB_HOST') !== '');

if (! $ready) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'DESATARA demo configuration is not ready.';
    return;
}

// Vercel Functions have a read-only bundle; only /tmp is writable.
$temporaryRoot = '/tmp/desatara';
$directoryPaths = [
    $temporaryRoot,
    $temporaryRoot.'/views',
    $temporaryRoot.'/cache',
];

foreach ($directoryPaths as $path) {
    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo 'DESATARA demo runtime storage is unavailable.';
        return;
    }
}

$runtimePaths = [
    'VIEW_COMPILED_PATH' => $temporaryRoot.'/views',
    'APP_CONFIG_CACHE' => $temporaryRoot.'/cache/config.php',
    'APP_EVENTS_CACHE' => $temporaryRoot.'/cache/events.php',
    'APP_PACKAGES_CACHE' => $temporaryRoot.'/cache/packages.php',
    'APP_ROUTES_CACHE' => $temporaryRoot.'/cache/routes.php',
    'APP_SERVICES_CACHE' => $temporaryRoot.'/cache/services.php',
];

foreach ($runtimePaths as $name => $value) {
    putenv($name.'='.$value);
    $_SERVER[$name] = $value;
    $_ENV[$name] = $value;
}

require __DIR__.'/../public/index.php';
