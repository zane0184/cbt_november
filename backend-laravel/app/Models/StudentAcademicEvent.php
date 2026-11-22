<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'effective_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
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
