<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

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
        // Permission decisions must not trust a previously loaded roles/permissions
        // relation. A user with conflicting portal roles is never authorized.
        $roleNames = $this->roles()->pluck('name');
        if ($roleNames->count() !== 1
            || !$this->hasActivePersistedTenantIdentity()
            || !$this->hasActiveAcademicProfileForRole((string) $roleNames->first())) {
            return false;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('permissions.name', $permission))
            ->exists();
    }

    /**
     * Require one persisted, active portal role in the resolved tenant.
     * This intentionally ignores stale in-memory Eloquent role relations.
     */
    public function hasExactRole(string $role): bool
    {
        if (!$this->hasActivePersistedTenantIdentity()) {
            return false;
        }

        $roleNames = $this->roles()->pluck('name');

        return $roleNames->count() === 1
            && $roleNames->first() === $role
            && $this->hasActiveAcademicProfileForRole($role);
    }

    /**
     * Check a permission through the single persisted portal role only.
     */
    public function hasExactPermission(string $role, string $permission): bool
    {
        if (!$this->hasExactRole($role)) {
            return false;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('permissions.name', $permission))
            ->exists();
    }

    private function hasActiveAcademicProfileForRole(string $role): bool
    {
        if ($role === 'GURU') {
            return DB::table('teacher_profiles')
                ->where('user_id', $this->getKey())
                ->where('tenant_id', $this->tenant_id)
                ->whereNull('deleted_at')
                ->exists();
        }

        if ($role === 'SISWA') {
            return DB::table('student_profiles')
                ->where('user_id', $this->getKey())
                ->where('tenant_id', $this->tenant_id)
                ->whereNull('deleted_at')
                ->exists();
        }

        // Admin Madrasah has no separate academic profile table.
        return true;
    }

    private function hasActivePersistedTenantIdentity(): bool
    {
        if (!$this->exists || !$this->getKey() || !$this->tenant_id) {
            return false;
        }

        $tenantContextId = app(\App\Contexts\TenantContext::class)->getTenantId();
        if (!$tenantContextId || (string) $tenantContextId !== (string) $this->tenant_id) {
            return false;
        }

        $activeUser = DB::table('users')
            ->where('id', $this->getKey())
            ->where('tenant_id', $this->tenant_id)
            ->whereNull('deleted_at')
            ->exists();

        $activeTenant = DB::table('tenants')
            ->where('id', $this->tenant_id)
            ->whereNull('deleted_at')
            ->where('status', 'ACTIVE')
            ->exists();

        return $activeUser && $activeTenant;
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
