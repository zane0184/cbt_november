<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestionSnapshot extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'source_question_id', 'source_question_version_id',
        'sequence', 'points', 'snapshot_stem', 'snapshot_explanation',
        'snapshot_options', 'snapshot_media', 'metadata'
    ];

    protected $casts = [
        'snapshot_options' => 'json',
        'snapshot_media' => 'json',
        'metadata' => 'json',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
