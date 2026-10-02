<?php

namespace App\Support\Tenancy;

final class TenantCacheKey
{
    public static function make(int $tenantId, string $domain, string $identifier, string|int $version = 1): string
    {
        foreach ([$domain, $identifier, (string) $version] as $part) {
            if ($part === '' || str_contains($part, ':')) {
                throw new \InvalidArgumentException('Cache key parts must be non-empty and colon-free.');
            }
        }

        return "desatara:{$tenantId}:{$domain}:{$identifier}:{$version}";
    }
}
