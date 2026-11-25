<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KktpVersion extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'learning_objective_id',
        'version',
        'threshold',
        'created_by',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'version' => 'integer',
    ];

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
