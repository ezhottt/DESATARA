<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** @property array<string, mixed> $regulatory_context */
/** @property array<string, mixed> $schema_definition */
class ReportTemplateVersion extends Model
{
    protected $fillable = ['report_template_id', 'version', 'regulatory_context', 'schema_definition', 'effective_from', 'effective_until', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['regulatory_context' => 'array', 'schema_definition' => 'array', 'effective_from' => 'immutable_date', 'effective_until' => 'immutable_date', 'published_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $dirty = $version->getDirty();
            unset($dirty['updated_at']);
            if ($version->getOriginal('status') === 'published' && $dirty !== ['status' => 'superseded']) {
                throw new LogicException('Published report template versions are immutable.');
            }
        });
    }
}
