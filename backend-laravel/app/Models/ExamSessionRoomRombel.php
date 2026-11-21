<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionRoomRombel extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'exam_session_room_id', 'rombel_id', 'status',
        'assigned_by', 'released_at', 'released_by', 'release_reason',
    ];

    protected $casts = ['released_at' => 'datetime'];

    public function sessionRoom(): BelongsTo
    {
        return $this->belongsTo(ExamSessionRoom::class, 'exam_session_room_id');
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
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