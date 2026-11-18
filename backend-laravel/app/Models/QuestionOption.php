<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'question_version_id', 'option_key', 'content', 'sequence',
        'is_correct', 'score_weight', 'matching_key', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'json',
        'is_correct' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id');
    }

    public function getKeyAttribute()
    {
        return $this->option_key;
    }

    public function getOptionTextAttribute()
    {
        return $this->content;
    }
}
