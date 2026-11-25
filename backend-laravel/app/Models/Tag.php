<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['name', 'slug'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
