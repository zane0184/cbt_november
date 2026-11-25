<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'owner_type', 'owner_user_id', 'question_code', 'status', 'visibility',
        'curriculum_id', 'subject_id', 'phase_id', 'grade_level_id', 'created_by', 'updated_by'
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(QuestionVersion::class)->where('is_current', true);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(QuestionPublication::class);
    }
}
