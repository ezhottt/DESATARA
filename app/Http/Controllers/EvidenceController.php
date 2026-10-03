<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Evidence\QrTokenService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EvidenceController extends Controller
{
    public function download(string $uuid, TenantContext $context): Response
    {
        $document = Document::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        abort_unless($document->links()->exists(), 404);
        abort_unless($document->visibility === 'private' && $document->storage_state === 'stored' && $document->malware_scan_status !== 'infected', 404);

        return Storage::disk($document->storage_disk)->download($document->storage_path, $document->original_filename, ['Content-Type' => $document->mime_type]);
    }

    public function publicQr(string $token, QrTokenService $qr): JsonResponse
    {
        return response()->json($qr->publicProjection($token));
    }
}
