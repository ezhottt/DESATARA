<?php

namespace App\Support;

use RuntimeException;

final class PublicDemoGuard
{
    /**
     * Validate that an internet-facing demo can only use its isolated Neon DB.
     *
     * @return string Secret password for provisioning a demo account.
     */
    public static function assertIsolated(): string
    {
        if (! app()->environment('demo') || config('public_demo.enabled') !== true) {
            throw new RuntimeException('Online demo requires APP_ENV=demo and DESATARA_DEMO_ENABLED=true.');
        }

        if (config('app.debug') !== false || config('database.default') !== 'pgsql') {
            throw new RuntimeException('Online demo must disable debugging and use PostgreSQL.');
        }

        $db = config('database.connections.pgsql', []);
        $url = $db['url'] ?? null;
        $hasUrl = is_string($url) && $url !== '';
        $host = $hasUrl ? parse_url($url, PHP_URL_HOST) : ($db['host'] ?? null);
        $database = $hasUrl ? ltrim((string) parse_url($url, PHP_URL_PATH), '/') : ($db['database'] ?? null);

        if (! is_string($host) || ! str_ends_with($host, '.aws.neon.tech') || $database !== 'desatara_demo') {
            throw new RuntimeException('Online demo database must be the isolated desatara_demo on Neon.');
        }

        $password = config('public_demo.admin_password');

        if (! is_string($password) || strlen($password) < 20) {
            throw new RuntimeException('Online demo requires a secret demo admin password at least 20 characters long.');
        }

        return $password;
    }
}
