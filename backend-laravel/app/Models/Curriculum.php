<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'curricula';

    protected $fillable = ['name', 'code', 'description', 'status', 'is_active'];

    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }
}
