<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class WorkflowVersion extends Model
{
    protected $fillable = ['workflow_definition_id', 'version', 'effective_from', 'effective_until', 'status', 'definition', 'published_at'];

    protected function casts(): array
    {
        return ['definition' => 'array', 'effective_from' => 'date', 'effective_until' => 'date', 'published_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $dirty = $version->getDirty();
            unset($dirty['updated_at']);
            if ($version->getOriginal('status') === 'published' && $dirty !== ['status' => 'superseded']) {
                throw new LogicException('Published workflow versions are immutable.');
            }
        });
    }
}
