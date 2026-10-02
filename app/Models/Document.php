<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'document_type', 'document_number', 'document_date', 'storage_disk', 'storage_path', 'original_filename', 'mime_type', 'size_bytes', 'checksum', 'uploaded_by', 'malware_scan_status', 'storage_state', 'visibility', 'classification', 'supersedes_document_id'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'supersedes_document_id');
    }

    /** @return HasMany<DocumentLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class);
    }

    /** @return HasMany<AssetPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class);
    }
}
