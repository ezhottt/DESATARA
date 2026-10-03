<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\ResponsibleParty;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetSearchController extends Controller
{
    public function __invoke(Request $request, TenantContext $context): Response
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim((string) ($validated['q'] ?? ''));
        $like = '%'.$query.'%';
        $assets = Asset::query()->where('tenant_id', $context->id());

        if ($query !== '') {
            $assets->where(fn ($builder) => $builder
                ->where('name', 'like', $like)
                ->orWhere('asset_code', 'like', $like)
                ->orWhere('register_number', 'like', $like));
        }

        $locations = AssetLocation::query()->where('tenant_id', $context->id())->when($query !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)))->limit(10)->get(['id', 'name', 'code']);
        $responsibleParties = ResponsibleParty::query()->where('tenant_id', $context->id())->where('status', 'active')->limit(10)->get(['id', 'party_type', 'membership_id', 'organizational_unit_id']);

        return Inertia::render('Search', [
            'query' => $query,
            'assets' => $assets->orderBy('name')->paginate(25)->withQueryString()->through(fn (Asset $asset) => [
                'uuid' => $asset->uuid,
                'name' => $asset->name,
                'asset_code' => $asset->asset_code,
                'register_number' => $asset->register_number,
                'condition' => $asset->condition,
                'lifecycle_status' => $asset->lifecycle_status,
                'verification_status' => $asset->verification_status,
            ]),
            'locations' => $locations,
            'responsible_parties' => $responsibleParties,
        ]);
    }
}
