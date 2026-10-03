<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = ['tenant_id', 'approval_request_id', 'sequence', 'required_permission_id', 'authority_requirement', 'status'];

    protected function casts(): array
    {
        return ['authority_requirement' => 'array', 'acted_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Permission, $this> */
    public function requiredPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }
}
