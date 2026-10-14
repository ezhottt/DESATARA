<?php

namespace App\Support;

use RuntimeException;

final class PublicDemoGuard
{
    public static function assertIsolated(): string
    {
        if (! app()->environment('demo') || config('public_demo.enabled') !== true) {
            throw new RuntimeException('Public demo requires APP_ENV=demo and DESATARA_DEMO_ENABLED=true.');
        }

        if (config('app.debug') !== false || config('database.default') !== 'pgsql') {
            throw new RuntimeException('Public demo requires APP_DEBUG=false and PostgreSQL.');
        }

        $connection = config('database.connections.pgsql');
        $url = $connection['url'] ?? null;
        $host = is_string($url) && $url !== '' ? parse_url($url, PHP_URL_HOST) : ($connection['host'] ?? null);
        $database = is_string($url) && $url !== ''
            ? ltrim((string) parse_url($url, PHP_URL_PATH), '/')
            : ($connection['database'] ?? null);

        if (! is_string($host) || ! str_ends_with($host, '.railway.internal') || $database !== 'desatara_demo') {
            throw new RuntimeException('Public demo requires isolated desatara_demo PostgreSQL on Railway private networking.');
        }

        $password = config('public_demo.admin_password');

        if (! is_string($password) || strlen($password) < 20) {
            throw new RuntimeException('Public demo requires a private administrator password of at least 20 characters.');
        }

        return $password;
    }
}
