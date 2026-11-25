<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeLevel extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = ['phase_id', 'name', 'code', 'level', 'status'];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }
}
