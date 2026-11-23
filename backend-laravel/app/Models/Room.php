<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'rooms';

    protected $fillable = ['code', 'name', 'capacity', 'is_active'];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function examRoomAssignments(): HasMany
    {
        return $this->hasMany(ExamRoomAssignment::class);
    }
}
