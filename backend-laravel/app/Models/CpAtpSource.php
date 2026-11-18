<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpAtpSource extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'cp_achievement_id',
        'atp_achievement_id',
        'atp_score_snapshot',
        'atp_status_snapshot',
        'atp_calculation_version_snapshot',
        'created_at',
    ];

    protected $casts = [
        'atp_score_snapshot' => 'decimal:2',
        'atp_calculation_version_snapshot' => 'integer',
        'created_at' => 'datetime',
    ];

    public function cpAchievement(): BelongsTo
    {
        return $this->belongsTo(CpAchievement::class, 'cp_achievement_id');
    }

    public function atpAchievement(): BelongsTo
    {
        return $this->belongsTo(AtpAchievement::class, 'atp_achievement_id');
    }
}
