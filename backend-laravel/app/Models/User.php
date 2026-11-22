<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $tenantId = null;

            // 1. Check TenantContext from container
            if (app()->bound(\App\Contexts\TenantContext::class)) {
                $tenantId = app(\App\Contexts\TenantContext::class)->getTenantId();
            }

            // 2. Check TenantContext singleton directly
            if (!$tenantId && $instance = \App\Contexts\TenantContext::getInstance()) {
                $tenantId = $instance->getTenantId();
            }

            // 3. Check current authenticated user
            if (!$tenantId && auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
            }

            // 4. If already set on model (manually or via forceCreate)
            if (!$tenantId && $model->tenant_id) {
                $tenantId = $model->tenant_id;
            }

            if (!$tenantId) {
                throw new \RuntimeException("Attempted to create a User model without a valid tenant context.");
            }

            $model->tenant_id = $tenantId;

            // Every account receives a tenant-unique username. Role-specific login
            // still resolves Guru by NIP/NIK first and Siswa by NISN only.
            $username = strtolower(trim((string) ($model->username ?? '')));
            if ($username === '') {
                $emailLocal = strstr((string) ($model->email ?? ''), '@', true);
                $username = preg_replace('/[^a-z0-9._-]+/', '-', strtolower((string) $emailLocal)) ?: '';
                $username = trim(substr($username, 0, 60), '._-');
                if ($username === '') $username = 'user';
            }
            $baseUsername = $username;
            $attempt = 0;
            while (static::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->exists()) {
                $attempt++;
                $username = substr($baseUsername, 0, 48).'_'.strtolower(\Illuminate\Support\Str::random(8));
                if ($attempt > 10) throw new \RuntimeException('Unable to allocate unique tenant username.');
            }
            $model->username = $username;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    public function teacherProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function studentProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
