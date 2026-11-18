<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionPublication extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'question_id', 'source_tenant_id', 'source_user_id',
        'share_scope', 'status', 'published_by', 'published_at'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
