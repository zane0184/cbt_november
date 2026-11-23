<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentTaxonomyOption extends Model
{
    use HasUuids;

    protected $table = 'assessment_taxonomy_options';

    protected $fillable = [
        'tenant_id', 'dimension', 'code', 'label', 'description', 'version_code',
        'source_id', 'effective_from', 'effective_until', 'status',
        'is_selectable', 'is_official', 'is_editable', 'sort_order', 'supersedes_id', 'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_selectable' => 'boolean',
        'is_official' => 'boolean',
        'is_editable' => 'boolean',
        'metadata' => 'array',
    ];

    public function scopeSelectableForTenant(Builder $query, string $tenantId, string $dimension): Builder
    {
        $today = now()->toDateString();

        return $query
            ->whereIn('tenant_id', array_values(array_unique([
                $tenantId,
                Tenant::SYSTEM_TENANT_ID,
            ])))
            ->where('dimension', $dimension)
            ->where('status', 'ACTIVE')
            ->where('is_selectable', true)
            ->where(function (Builder $dateQuery) use ($today): void {
                $dateQuery->whereNull('effective_from')->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function (Builder $dateQuery) use ($today): void {
                $dateQuery->whereNull('effective_until')->orWhereDate('effective_until', '>=', $today);
            });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CurriculumReferenceSource::class, 'source_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }
}
