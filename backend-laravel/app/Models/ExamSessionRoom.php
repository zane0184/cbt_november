<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSessionRoom extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'exam_session_id', 'exam_room_id', 'capacity_override', 'status',
    ];

    protected $casts = ['capacity_override' => 'integer'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    public function rombelMappings(): HasMany
    {
        return $this->hasMany(ExamSessionRoomRombel::class, 'exam_session_room_id');
    }

    public function participantAssignments(): HasMany
    {
        return $this->hasMany(ExamParticipantRoomAssignment::class, 'exam_session_room_id');
    }
}