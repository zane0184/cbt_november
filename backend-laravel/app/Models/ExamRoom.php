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

    public function sessionRooms(): HasMany
    {
        return $this->hasMany(ExamSessionRoom::class, 'exam_room_id');
    }
}