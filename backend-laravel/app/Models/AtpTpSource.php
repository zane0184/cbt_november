<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtpTpSource extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'atp_achievement_id',
        'tp_achievement_id',
        'tp_score_snapshot',
        'tp_status_snapshot',
        'tp_calculation_version_snapshot',
        'created_at',
    ];

    protected $casts = [
        'tp_score_snapshot' => 'decimal:2',
        'tp_calculation_version_snapshot' => 'integer',
        'created_at' => 'datetime',
    ];

    public function atpAchievement(): BelongsTo
    {
        return $this->belongsTo(AtpAchievement::class, 'atp_achievement_id');
    }

    public function tpAchievement(): BelongsTo
    {
        return $this->belongsTo(TpAchievement::class, 'tp_achievement_id');
    }
}
