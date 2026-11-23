<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRoomAssignment extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'room_id', 'session_number', 'session_token',
        'token_expires_at', 'scheduled_start', 'scheduled_end',
        'merge_room', 'created_by',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'token_expires_at' => 'datetime',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'merge_room' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function proctors(): HasMany
    {
        return $this->hasMany(ExamRoomProctor::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function activeProctors(): HasMany
    {
        return $this->proctors()->where('status', 'ACTIVE')->whereNull('revoked_at');
    }

    public function isScheduledNow(): bool
    {
        return now()->betweenIncluded($this->scheduled_start, $this->scheduled_end);
    }
}
