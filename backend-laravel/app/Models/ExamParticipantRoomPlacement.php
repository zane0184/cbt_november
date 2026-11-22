<?php

namespace App\\Models;

use App\\Traits\\TenantScoped;
use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ExamParticipantRoomPlacement extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'exam_id', 'participant_id', 'exam_room_id', 'status',
        'assigned_by', 'assigned_at', 'released_at', 'released_by', 'release_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'participant_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
