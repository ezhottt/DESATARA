<?php

namespace App\Services\Evidence;

use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\SubjectType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DocumentService
{
    private const MAX_BYTES = 10_485_760;

    public function store(Tenant $tenant, User $user, Asset $asset, UploadedFile $file, string $documentType, ?Document $supersedes = null): Document
    {
        abort_unless($asset->tenant_id === $tenant->id, 404);
        $this->validateFile($file);
        if ($supersedes && $supersedes->tenant_id !== $tenant->id) {
            abort(404);
        }
        if ($supersedes && ! DocumentLink::query()->where('tenant_id', $tenant->id)->where('document_id', $supersedes->id)->where('subject_id', $asset->id)->exists()) {
            abort(404);
        }

        $uuid = (string) Str::uuid();
        $path = 'documents/'.$tenant->uuid.'/'.$uuid;
        $disk = Storage::disk('private');
        $disk->putFileAs(dirname($path), $file, basename($path));

        return DB::transaction(function () use ($tenant, $user, $asset, $file, $documentType, $supersedes, $path): Document {
            $document = Document::query()->create([
                'tenant_id' => $tenant->id,
                'document_type' => $documentType,
                'storage_disk' => 'private',
                'storage_path' => $path,
                'original_filename' => basename($file->getClientOriginalName()),
                'mime_type' => $this->detectedMime($file),
                'size_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by' => $user->id,
                'malware_scan_status' => 'pending',
                'storage_state' => 'quarantined',
                'visibility' => 'private',
                'classification' => 'sensitive',
                'supersedes_document_id' => $supersedes?->id,
            ]);
            $subject = SubjectType::query()->where('code', 'asset')->where('status', 'active')->firstOrFail();
            DocumentLink::query()->create(['tenant_id' => $tenant->id, 'document_id' => $document->id, 'subject_type_id' => $subject->id, 'subject_id' => $asset->id, 'purpose' => $documentType, 'created_by' => $user->id]);

            return $document;
        });
    }

    private function validateFile(UploadedFile $file): void
    {
        $name = $file->getClientOriginalName();
        if ($name === '' || $name !== basename($name) || str_contains($name, '..')) {
            throw new InvalidArgumentException('Unsafe filename.');
        }
        if (($file->getSize() ?: 0) < 1 || $file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('Invalid file size.');
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new InvalidArgumentException('Unsupported file type.');
        }
        $mime = $this->detectedMime($file);
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if ($mime !== $allowed[$extension]) {
            throw new InvalidArgumentException('File signature does not match its extension.');
        }
    }

    private function detectedMime(UploadedFile $file): string
    {
        $bytes = (string) file_get_contents($file->getRealPath(), false, null, 0, 12);
        if (str_starts_with($bytes, '%PDF-')) {
            return 'application/pdf';
        }
        if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
            return 'image/jpeg';
        }
        if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
            return 'image/png';
        }

        return (string) $file->getMimeType();
    }
}
