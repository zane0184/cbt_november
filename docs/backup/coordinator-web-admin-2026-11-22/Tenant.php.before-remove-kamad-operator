<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const SYSTEM_TENANT_ID = '00000000-0000-0000-0000-000000000000';
    public const SYSTEM_TENANT_CODE = 'SYSTEM_GLOBAL';

    protected $fillable = [
        'id',
        'name',
        'code',
        'nsm',
        'npsn',
        'address',
        'phone',
        'email',
        'logo_path',
        'status',
    ];
}
