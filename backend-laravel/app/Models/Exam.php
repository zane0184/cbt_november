<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'assessment_id', 'title', 'instructions', 'status',
        'duration_seconds', 'start_at', 'end_at', 'timezone',
        'attempt_limit', 'shuffle_questions', 'shuffle_options',
        'show_result', 'show_correct_answer', 'allow_resume',
        'auto_submit', 'require_token', 'token', 'token_mode',
        'revision_number', 'created_by', 'published_at', 'locked_at', 'assessment_context_snapshot'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'require_token' => 'boolean',
        'allow_resume' => 'boolean',
        'auto_submit' => 'boolean',
        'show_result' => 'boolean',
        'show_correct_answer' => 'boolean',
        'assessment_context_snapshot' => 'array',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Subject::class,
            Assessment::class,
            'id',
            'id',
            'assessment_id',
            'subject_id'
        );
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ExamQuestionSnapshot::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isLocked(): bool
    {
        return !is_null($this->locked_at);
    }

    public function getCurrentStatusAttribute(): string
    {
        if ($this->status === 'ARCHIVED') return 'ARCHIVED';
        if ($this->status === 'CLOSED') return 'CLOSED';
        if (!$this->isLocked()) return 'DRAFT';

        $now = now();
        if ($now->lt($this->start_at)) return 'SCHEDULED';
        if ($now->lt($this->end_at)) return 'ONGOING';

        return 'CLOSED';
    }

    public function isOngoing(): bool
    {
        if (!$this->isLocked()) return false;
        if ($this->status === 'CLOSED' || $this->status === 'ARCHIVED') return false;

        return now()->between($this->start_at, $this->end_at);
    }

    public function canBeEdited(): bool
    {
        return !$this->isLocked() && $this->status === 'DRAFT';
    }

    public function canStartAttempt(): bool
    {
        return $this->isLocked() && $this->isOngoing();
    }
}
