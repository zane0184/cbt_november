<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudentAcademicEvent extends Model
{
    use HasUuids, TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'from_rombel_id',
        'to_rombel_id',
        'event_type',
        'effective_date',
        'reason',
        'performed_by',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Student academic events are append-only and cannot be updated.'));
        static::deleting(fn () => throw new LogicException('Student academic events are append-only and cannot be deleted.'));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id')->withTrashed();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function fromRombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class, 'from_rombel_id');
    }

    public function toRombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class, 'to_rombel_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
