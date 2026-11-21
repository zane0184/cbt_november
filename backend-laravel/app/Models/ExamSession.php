<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'name', 'session_number', 'starts_at', 'ends_at',
        'schedule_revision', 'status',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'schedule_revision' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function sessionRooms(): HasMany
    {
        return $this->hasMany(ExamSessionRoom::class, 'exam_session_id');
    }
}