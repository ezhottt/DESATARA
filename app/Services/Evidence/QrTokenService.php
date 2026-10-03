<?php

namespace App\Services\Evidence;

use App\Models\Asset;
use App\Models\AssetQrToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QrTokenService
{
    /** @return array{0:string,1:AssetQrToken} */
    public function issue(Tenant $tenant, User $user, Asset $asset, ?int $rotatedFromId = null): array
    {
        abort_unless($asset->tenant_id === $tenant->id, 404);
        $raw = Str::random(64);
        $token = AssetQrToken::query()->create(['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'token_hash' => hash('sha256', $raw), 'status' => 'active', 'issued_at' => now(), 'rotated_from_id' => $rotatedFromId, 'created_by' => $user->id]);

        return [$raw, $token];
    }

    public function resolve(string $raw): ?AssetQrToken
    {
        if ($raw === '' || strlen($raw) < 32) {
            return null;
        }

        return AssetQrToken::query()->where('token_hash', hash('sha256', $raw))->where('status', 'active')->first();
    }

    public function revoke(AssetQrToken $token): void
    {
        $token->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();
    }

    /** @return array{asset_code:string|null,name:string,classification:string,status:string} */
    public function publicProjection(string $raw): array
    {
        $token = $this->resolve($raw);
        abort_unless($token !== null, 404);
        $asset = Asset::query()->with('classification')->where('tenant_id', $token->tenant_id)->whereKey($token->asset_id)->firstOrFail();

        return ['asset_code' => $asset->asset_code, 'name' => $asset->name, 'classification' => $asset->classification?->name, 'status' => $asset->lifecycle_status];
    }

    /** @return array{0:string,1:AssetQrToken} */
    public function rotate(Tenant $tenant, User $user, Asset $asset, AssetQrToken $old): array
    {
        abort_unless($old->tenant_id === $tenant->id && $old->asset_id === $asset->id, 404);

        return DB::transaction(function () use ($tenant, $user, $asset, $old): array {
            $this->revoke($old);

            return $this->issue($tenant, $user, $asset, $old->id);
        });
    }
}
