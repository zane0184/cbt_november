<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRoom extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['code', 'name', 'location', 'capacity', 'status'];

    protected $casts = ['capacity' => 'integer'];

    /** Exams using this reusable physical room (direct simultaneous model). */
    public function exams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_room_exam', 'exam_room_id', 'exam_id')
            ->withPivot(['id', 'tenant_id', 'capacity_override', 'status', 'assigned_by'])
            ->withTimestamps();
    }

    public function examAllocations(): HasMany
    {
        return $this->hasMany(ExamRoomExam::class, 'exam_room_id');
    }

    public function participantPlacements(): HasMany
    {
        return $this->hasMany(ExamParticipantRoomPlacement::class, 'exam_room_id');
    }

    /** Legacy history relation; new operational code must not use it. */
    public function sessionRooms(): HasMany
    {
        return $this->hasMany(ExamSessionRoom::class, 'exam_room_id');
    }
}