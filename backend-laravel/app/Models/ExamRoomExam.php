<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamRoomExam extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'exam_room_exam';

    protected $fillable = [
        'tenant_id', 'exam_id', 'exam_room_id', 'capacity_override',
        'status', 'assigned_by',
    ];

    protected $casts = ['capacity_override' => 'integer'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
