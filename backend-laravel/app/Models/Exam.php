<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    public const CATEGORY_REGULAR = 'REGULAR';
    public const CATEGORY_FORMATIF = 'FORMATIF';
    public const CATEGORY_SUMATIF_LINGKUP = 'SUMATIF_LINGKUP';
    public const CATEGORY_SURVEI_KBC = 'SURVEI_KBC';
    public const CATEGORY_REMEDIAL = 'REMEDIAL';
    public const CATEGORY_TRYOUT_MANDIRI = 'TRYOUT_MANDIRI';
    public const CATEGORY_ASTS = 'ASTS';
    public const CATEGORY_ASAS = 'ASAS';
    public const CATEGORY_ASAT = 'ASAT';
    public const CATEGORY_UM = 'UM';
    public const CATEGORY_SIMULASI_AN = 'SIMULASI_AN';

    public const CENTRALIZED_CATEGORIES = [
        self::CATEGORY_ASTS, self::CATEGORY_ASAS, self::CATEGORY_ASAT,
        self::CATEGORY_UM, self::CATEGORY_SIMULASI_AN,
    ];

    public const ALL_CATEGORIES = [
        self::CATEGORY_REGULAR, self::CATEGORY_FORMATIF, self::CATEGORY_SUMATIF_LINGKUP,
        self::CATEGORY_SURVEI_KBC, self::CATEGORY_REMEDIAL, self::CATEGORY_TRYOUT_MANDIRI,
        self::CATEGORY_ASTS, self::CATEGORY_ASAS, self::CATEGORY_ASAT,
        self::CATEGORY_UM, self::CATEGORY_SIMULASI_AN,
    ];
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected static function booted(): void
    {
        static::saving(function (self $exam): void {
            if (!$exam->category) {
                $exam->category = self::CATEGORY_REGULAR;
            }
            if (!in_array($exam->category, self::ALL_CATEGORIES, true)) {
                throw new \InvalidArgumentException('Kategori ujian tidak dikenal: '.$exam->category);
            }
            // Category is the source of truth; clients cannot forge the centralized flag.
            $exam->is_centralized = self::isCentralizedCategory($exam->category);
        });
    }

    protected $fillable = [
        'assessment_id', 'title', 'category', 'is_centralized', 'instructions', 'status',
        'duration_seconds', 'start_at', 'end_at', 'timezone',
        'attempt_limit', 'shuffle_questions', 'shuffle_options',
        'show_result', 'show_correct_answer', 'allow_resume',
        'auto_submit', 'require_token', 'token', 'token_mode',
        'revision_number', 'created_by', 'published_at', 'locked_at'
    ];

    protected $casts = [
        'is_centralized' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'require_token' => 'boolean',
        'allow_resume' => 'boolean',
        'auto_submit' => 'boolean',
        'show_result' => 'boolean',
        'show_correct_answer' => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Subject::class,
            Assessment::class,
            'id',
            'id',
            'assessment_id',
            'subject_id'
        );
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ExamQuestionSnapshot::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(ExamRoomAssignment::class);
    }

    public function isCentralized(): bool
    {
        return in_array($this->category, self::CENTRALIZED_CATEGORIES, true)
            && (bool) $this->is_centralized;
    }

    public static function isCentralizedCategory(?string $category): bool
    {
        return in_array($category, self::CENTRALIZED_CATEGORIES, true);
    }

    public function isLocked(): bool
    {
        return !is_null($this->locked_at);
    }

    public function getCurrentStatusAttribute(): string
    {
        if ($this->status === 'ARCHIVED') return 'ARCHIVED';
        if ($this->status === 'CLOSED') return 'CLOSED';
        if (!$this->isLocked()) return 'DRAFT';

        $now = now();
        if ($now->lt($this->start_at)) return 'SCHEDULED';
        if ($now->lt($this->end_at)) return 'ONGOING';

        return 'CLOSED';
    }

    public function isOngoing(): bool
    {
        if (!$this->isLocked()) return false;
        if ($this->status === 'CLOSED' || $this->status === 'ARCHIVED') return false;

        return now()->between($this->start_at, $this->end_at);
    }

    public function canBeEdited(): bool
    {
        return !$this->isLocked() && $this->status === 'DRAFT';
    }

    public function canStartAttempt(): bool
    {
        return $this->isLocked() && $this->isOngoing();
    }
}
