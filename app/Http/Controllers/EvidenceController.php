<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Tenant;
use App\Services\Assets\AssetIdentityService;
use App\Services\Evidence\QrTokenService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EvidenceController extends Controller
{
    public function download(Request $request, string $uuid, TenantContext $context): Response
    {
        $document = Document::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        abort_unless($document->links()->exists(), 404);
        abort_unless($document->visibility === 'private' && $document->storage_state === 'stored' && $document->malware_scan_status === 'clean', 404);

        AuditLog::query()->create([
            'tenant_id' => $context->id(),
            'actor_id' => $request->user()->id,
            'action' => 'document.download.authorized',
            'subject_type' => 'document',
            'subject_id' => $document->id,
            'after_state' => ['delivery' => 'authorized'],
            'occurred_at' => now(),
        ]);

        return Storage::disk($document->storage_disk)->download($document->storage_path, $document->original_filename, ['Content-Type' => $document->mime_type]);
    }

    public function publicAsset(string $uuid, AssetIdentityService $identity): JsonResponse
    {
        $asset = Asset::query()->with('classification')->where('uuid', $uuid)->firstOrFail();
        $tenant = Tenant::query()->findOrFail($asset->tenant_id);
        $label = $identity->labelPayload($tenant, $asset);

        return response()->json([
            'name' => $label['name'],
            'inventory_code' => $label['inventory_code'],
            'acquisition_year' => $label['acquisition_year'],
            'status' => $asset->lifecycle_status,
            'village_name' => $label['village_name'],
        ]);
    }

    public function publicQr(string $token, QrTokenService $qr): JsonResponse
    {
        return response()->json($qr->publicProjection($token));
    }
}
