<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = 'ACTIVE';

    public $showCreateModal = false;
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $selectedRole = '';
    public $nip = '';
    public $nik = '';
    public $nisn = '';

    public $showEditModal = false;
    public $editingUserId = '';
    public $editingRole = '';
    public $editName = '';
    public $editUsername = '';
    public $editEmail = '';
    public $editNip = '';
    public $editNik = '';
    public $editNisn = '';

    public function mount(): void
    {
        $this->authorizeUserManagement();
    }

    /** Re-check authorization on every Livewire mutation; mount is not a mutation boundary. */
    private function authorizeUserManagement(): void
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id
            || !$user->hasRole('ADMIN_MADRASAH')
            || !$user->hasPermission('user.manage')) {
            abort(403, 'Akses ditolak. Diperlukan role Admin Madrasah dan permission user.manage.');
        }
    }

    protected function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $manageableRoles = ['GURU', 'SISWA'];
        $rules = [
            'name' => ['required','string','max:255'],
            'username' => ['nullable','string','min:3','max:80','regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users','username')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'email' => ['nullable','email','max:255', Rule::unique('users','email')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'password' => ['required','string','min:14'],
            'selectedRole' => ['required','string',Rule::in($manageableRoles)],
            'nip' => ['nullable','string','max:18'],
            'nik' => ['nullable','digits:16'],
            'nisn' => ['nullable','digits:10'],
        ];

        if ($this->selectedRole === 'GURU') {
            $rules['nip'][] = Rule::unique('teacher_profiles','nip')->where(fn ($q) => $q->where('tenant_id', $tenantId));
            $rules['nik'][] = Rule::unique('teacher_profiles','nik')->where(fn ($q) => $q->where('tenant_id', $tenantId));
        }
        if ($this->selectedRole === 'SISWA') {
            $rules['nisn'] = ['required','digits:10',Rule::unique('student_profiles','nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))];
        }

        return $rules;
    }

    private function managedRole(User $user): string
    {
        $roles = $user->roles()->pluck('name')->all();
        abort_unless(count($roles) === 1 && in_array($roles[0], ['GURU', 'SISWA'], true), 404);
        return $roles[0];
    }

    private function managedUser(string $userId, bool $lock = false): User
    {
        $query = User::withTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($userId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $user = $query->firstOrFail();
        $this->managedRole($user);

        return $user;
    }

    public function openCreateModal(): void
    {
        $this->authorizeUserManagement();
        $this->reset(['name','username','email','password','selectedRole','nip','nik','nisn']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createUser(): void
    {
        $this->authorizeUserManagement();
        $this->validate();
        if ($this->selectedRole === 'GURU' && trim($this->nip) === '' && trim($this->nik) === '') {
            $this->addError('nip', 'Guru wajib memiliki NIP atau NIK untuk identitas login.');
            return;
        }

        $tenantId = (string) auth()->user()->tenant_id;
        DB::transaction(function () use ($tenantId): void {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $this->validate();

            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => trim($this->name),
                'username' => trim($this->username) !== '' ? strtolower(trim($this->username)) : null,
                'email' => trim($this->email) !== '' ? strtolower(trim($this->email)) : null,
                'password' => Hash::make($this->password),
            ]);
            $role = Role::query()->whereIn('name', ['GURU', 'SISWA'])->where('name', $this->selectedRole)->firstOrFail();
            $user->roles()->sync([$role->id]);

            if ($this->selectedRole === 'GURU') {
                TeacherProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'nip' => trim($this->nip) !== '' ? trim($this->nip) : null,
                    'nik' => trim($this->nik) !== '' ? trim($this->nik) : null,
                    'employee_identifier' => trim($this->nip) !== '' ? trim($this->nip) : trim($this->nik),
                ]);
            } else {
                StudentProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'nisn' => trim($this->nisn),
                ]);
            }

            app(AuditService::class)->log('USER_CREATED', 'User', $user->id, null, [
                'name' => $user->name,
                'role' => $this->selectedRole,
                'username' => $user->username,
                'profile_type' => $this->selectedRole === 'GURU' ? 'TeacherProfile' : 'StudentProfile',
            ]);
        }, 3);

        $createdName = $this->name;
        $this->showCreateModal = false;
        $this->reset(['name','username','email','password','selectedRole','nip','nik','nisn']);
        $this->dispatch('notify', ['type' => 'success', 'message' => "Pengguna {$createdName} berhasil ditambahkan."]);
        session()->flash('message', "Pengguna {$createdName} berhasil ditambahkan.");
    }

    public function openEditModal(string $userId): void
    {
        $this->authorizeUserManagement();
        $user = $this->managedUser($userId);
        $role = $this->managedRole($user);
        $profile = $role === 'GURU'
            ? TeacherProfile::query()->where('tenant_id', $user->tenant_id)->where('user_id', $user->id)->firstOrFail()
            : StudentProfile::query()->where('tenant_id', $user->tenant_id)->where('user_id', $user->id)->firstOrFail();

        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->editingRole = $role;
        $this->editName = $user->name;
        $this->editUsername = $user->username;
        $this->editEmail = $user->email ?? '';
        $this->editNip = $role === 'GURU' ? ($profile->nip ?? '') : '';
        $this->editNik = $role === 'GURU' ? ($profile->nik ?? '') : '';
        $this->editNisn = $role === 'SISWA' ? ($profile->nisn ?? '') : '';
        $this->showEditModal = true;
    }

    private function editRules(User $user, string $role, TeacherProfile|StudentProfile $profile): array
    {
        $tenantId = (string) $user->tenant_id;
        $rules = [
            'editName' => ['required', 'string', 'max:255'],
            'editUsername' => [
                'required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id),
            ],
            'editEmail' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id),
            ],
        ];

        if ($role === 'GURU') {
            $rules['editNip'] = [
                'nullable', 'string', 'max:18',
                Rule::unique('teacher_profiles', 'nip')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id),
            ];
            $rules['editNik'] = [
                'nullable', 'digits:16',
                Rule::unique('teacher_profiles', 'nik')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id),
            ];
        } else {
            $rules['editNisn'] = [
                'required', 'digits:10',
                Rule::unique('student_profiles', 'nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id),
            ];
        }

        return $rules;
    }

    public function updateUser(): void
    {
        $this->authorizeUserManagement();
        abort_unless($this->editingUserId !== '', 404);
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId): void {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $user = $this->managedUser($this->editingUserId, true);
            $role = $this->managedRole($user);
            $profile = $role === 'GURU'
                ? TeacherProfile::query()->where('tenant_id', $tenantId)->where('user_id', $user->id)->lockForUpdate()->firstOrFail()
                : StudentProfile::query()->where('tenant_id', $tenantId)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $data = $this->validate($this->editRules($user, $role, $profile));
            if ($role === 'GURU' && trim($data['editNip'] ?? '') === '' && trim($data['editNik'] ?? '') === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'editNip' => 'Guru wajib memiliki NIP atau NIK untuk identitas login.',
                ]);
            }

            $old = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $role,
                'profile' => $role === 'GURU'
                    ? ['nip' => $profile->nip, 'nik' => $profile->nik, 'employee_identifier' => $profile->employee_identifier]
                    : ['nisn' => $profile->nisn],
            ];

            $user->name = trim($data['editName']);
            $user->username = strtolower(trim($data['editUsername']));
            $user->email = trim($data['editEmail'] ?? '') !== '' ? strtolower(trim($data['editEmail'])) : null;
            $user->save();

            if ($role === 'GURU') {
                $profile->full_name = trim($data['editName']);
                $profile->nip = trim($data['editNip'] ?? '') !== '' ? trim($data['editNip']) : null;
                $profile->nik = trim($data['editNik'] ?? '') !== '' ? trim($data['editNik']) : null;
                $profile->employee_identifier = $profile->nip ?: $profile->nik;
            } else {
                $profile->full_name = trim($data['editName']);
                $profile->nisn = trim($data['editNisn']);
            }
            $profile->save();

            $new = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $role,
                'profile' => $role === 'GURU'
                    ? ['nip' => $profile->nip, 'nik' => $profile->nik, 'employee_identifier' => $profile->employee_identifier]
                    : ['nisn' => $profile->nisn],
            ];
            app(AuditService::class)->log('USER_UPDATED', 'User', $user->id, $old, $new);
        }, 3);

        $this->showEditModal = false;
        $this->editingUserId = '';
        $this->reset(['editName','editUsername','editEmail','editNip','editNik','editNisn','editingRole']);
        session()->flash('message', 'Data akun dan profil berhasil diperbarui. Perubahan tercatat pada audit log.');
    }

    public function deactivateUser(string $userId): void
    {
        $this->authorizeUserManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $userId): void {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $user = $this->managedUser($userId, true);

            if ($user->trashed()) {
                return;
            }

            $old = [
                'status' => 'ACTIVE',
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $this->managedRole($user),
                'deleted_at' => null,
            ];

            // Revoke API credentials and remember-me sessions before soft-deactivation.
            $user->tokens()->delete();
            $user->remember_token = Str::random(60);
            $user->save();

            // SoftDeletes makes the session user provider reject this account on its next request.
            // Do not delete from sessions.user_id: this installation's legacy column is integer,
            // while User IDs are UUIDs. API tokens are revoked above.
            $user->delete();

            app(AuditService::class)->log('USER_DEACTIVATED', 'User', $user->id, $old, [
                'status' => 'INACTIVE',
                'deleted_at' => $user->deleted_at?->toISOString(),
            ]);
        }, 3);

        session()->flash('message', 'Akun dinonaktifkan tanpa menghapus profil atau histori akademik. Seluruh token API dicabut.');
    }

    public function restoreUser(string $userId): void
    {
        $this->authorizeUserManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $userId): void {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $user = $this->managedUser($userId, true);

            if (!$user->trashed()) {
                return;
            }

            $old = [
                'status' => 'INACTIVE',
                'deleted_at' => $user->deleted_at?->toISOString(),
            ];
            $user->restore();

            app(AuditService::class)->log('USER_REACTIVATED', 'User', $user->id, $old, [
                'status' => 'ACTIVE',
                'deleted_at' => null,
                'name' => $user->name,
                'role' => $this->managedRole($user),
            ]);
        }, 3);

        session()->flash('message', 'Akun diaktifkan kembali. Profil, ID, dan histori lama tetap digunakan; token API harus dibuat ulang saat login.');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingRoleFilter(string $value): void
    {
        abort_unless($value === '' || in_array($value, ['GURU', 'SISWA'], true), 422);
        $this->resetPage();
    }
    public function updatingStatusFilter(string $value): void
    {
        abort_unless(in_array($value, ['ACTIVE', 'INACTIVE', 'ALL'], true), 422);
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizeUserManagement();
        $tenantId = auth()->user()->tenant_id;
        $users = User::withTrashed()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('name', ['GURU', 'SISWA']))
            ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->whereNotIn('name', ['GURU', 'SISWA']))
            ->withCount('roles')
            ->having('roles_count', '=', 1)
            ->with(['roles','teacherProfile','studentProfile'])
            ->when($this->statusFilter === 'ACTIVE', fn ($query) => $query->whereNull('deleted_at'))
            ->when($this->statusFilter === 'INACTIVE', fn ($query) => $query->whereNotNull('deleted_at'))
            ->when($this->search, function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter, fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $this->roleFilter)))
            ->latest()->paginate(15);

        $roles = Role::query()->whereIn('name', ['GURU','SISWA'])->orderBy('label')->get();

        return view('livewire.admin.user-management', ['users' => $users, 'roles' => $roles])->layout('layouts.app');
    }
}
