<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamParticipant extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'exam_id', 'user_id', 'rombel_id', 'status', 'eligible_from',
        'eligible_until', 'attempt_limit_override'
    ];

    protected $casts = [
        'eligible_from' => 'datetime',
        'eligible_until' => 'datetime',
        'attempt_limit_override' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'participant_id');
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(ExamParticipantRoomAssignment::class, 'participant_id');
    }
}
