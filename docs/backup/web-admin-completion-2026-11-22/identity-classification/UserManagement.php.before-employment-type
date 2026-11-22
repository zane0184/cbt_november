<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showResetPasswordModal = false;
    public $showAccountStatusModal = false;
    public $statusUserId = null;
    public $statusTarget = null;
    public $statusReason = '';
    public $statusFilter = '';
    public $editingUserId = null;
    public $editingRole = '';
    public $resetPasswordUserId = null;
    public $resetPassword = '';
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $selectedRole = '';
    public $nip = '';
    public $nik = '';
    public $nisn = '';
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

    /** Re-check authorization at every Livewire mutation and render boundary. */
    private function authorizeUserManagement(): void
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id
            || $user->roles()->count() !== 1
            || !$user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            || !$user->hasPermission('user.manage')) {
            abort(403, 'Akses ditolak. Diperlukan role Admin Madrasah dan permission user.manage.');
        }
    }

    /** Only a single-role Guru or Siswa account in the current tenant is manageable. */
    private function manageableUserQuery()
    {
        $tenantId = (string) auth()->user()->tenant_id;

        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['GURU', 'SISWA']))
            ->whereDoesntHave('roles', fn ($query) => $query->whereNotIn('name', ['GURU', 'SISWA']))
            ->withCount('roles')
            ->having('roles_count', '=', 1);
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
        if ($this->selectedRole === 'SISWA') $rules['nisn'] = ['required','digits:10',Rule::unique('student_profiles','nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))];
        return $rules;
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
        $tenantId = auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId): void {
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
                    'user_id' => $user->id, 'full_name' => trim($this->name),
                    'nip' => trim($this->nip) !== '' ? trim($this->nip) : null,
                    'nik' => trim($this->nik) !== '' ? trim($this->nik) : null,
                    'employee_identifier' => trim($this->nip) !== '' ? trim($this->nip) : (trim($this->nik) !== '' ? trim($this->nik) : null),
                ]);
            } elseif ($this->selectedRole === 'SISWA') {
                StudentProfile::query()->create(['user_id' => $user->id, 'full_name' => trim($this->name), 'nisn' => trim($this->nisn)]);
            }

            app(AuditService::class)->log('USER_CREATED', 'User', $user->id, null, [
                'name' => $user->name, 'role' => $this->selectedRole,
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
        $user = $this->manageableUserQuery()->with(['roles','teacherProfile','studentProfile'])->whereKey($userId)->firstOrFail();

        $this->editingUserId = (string) $user->id;
        $this->editingRole = $user->roles->first()->name;
        $this->editName = $user->name;
        $this->editUsername = $user->username;
        $this->editEmail = $user->email;
        $this->editNip = $user->teacherProfile?->nip ?? '';
        $this->editNik = $user->teacherProfile?->nik ?? '';
        $this->editNisn = $user->studentProfile?->nisn ?? '';
        abort_unless(($user->teacherProfile && !$user->studentProfile) || ($user->studentProfile && !$user->teacherProfile), 409, 'Profil akun tidak konsisten.');
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateUser(): void
    {
        $this->authorizeUserManagement();
        $userId = (string) $this->editingUserId;
        $user = $this->manageableUserQuery()->with(['teacherProfile','studentProfile'])->whereKey($userId)->firstOrFail();
        abort_unless(($user->teacherProfile && !$user->studentProfile) || ($user->studentProfile && !$user->teacherProfile), 409, 'Profil akun tidak konsisten.');
        $tenantId = (string) auth()->user()->tenant_id;
        $isGuru = $user->roles()->where('name', 'GURU')->exists();

        $rules = [
            'editName' => ['required','string','max:255'],
            'editUsername' => ['required','string','min:3','max:80','regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users','username')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id)],
            'editEmail' => ['nullable','email','max:255', Rule::unique('users','email')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id)],
        ];
        if ($isGuru) {
            $rules['editNip'] = ['nullable','string','max:18', Rule::unique('teacher_profiles','nip')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->teacherProfile->id)];
            $rules['editNik'] = ['nullable','digits:16', Rule::unique('teacher_profiles','nik')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->teacherProfile->id)];
        } else {
            $rules['editNisn'] = ['required','digits:10', Rule::unique('student_profiles','nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->studentProfile->id)];
        }
        $validated = $this->validate($rules);

        DB::transaction(function () use ($userId, $tenantId, $isGuru, $validated): void {
            $locked = $this->manageableUserQuery()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $oldValues = ['name' => $locked->name, 'username' => $locked->username, 'email' => $locked->email];
            $locked->name = trim($validated['editName']);
            $locked->username = strtolower(trim($validated['editUsername']));
            $locked->email = ($validated['editEmail'] ?? '') !== '' ? strtolower(trim($validated['editEmail'])) : null;
            $locked->save();

            if ($isGuru) {
                $profile = TeacherProfile::query()->where('tenant_id', $tenantId)->where('user_id', $locked->id)->lockForUpdate()->firstOrFail();
                $oldValues['nip'] = $profile->nip;
                $oldValues['nik'] = $profile->nik;
                $profile->full_name = $locked->name;
                $profile->nip = trim($validated['editNip'] ?? '') !== '' ? trim($validated['editNip']) : null;
                $profile->nik = trim($validated['editNik'] ?? '') !== '' ? trim($validated['editNik']) : null;
                $profile->employee_identifier = $profile->nip ?: $profile->nik;
                $profile->save();
            } else {
                $profile = StudentProfile::query()->where('tenant_id', $tenantId)->where('user_id', $locked->id)->lockForUpdate()->firstOrFail();
                $oldValues['nisn'] = $profile->nisn;
                $profile->full_name = $locked->name;
                $profile->nisn = trim($validated['editNisn']);
                $profile->save();
            }

            app(AuditService::class)->log('USER_UPDATED', 'User', $locked->id, $oldValues, [
                'name' => $locked->name, 'username' => $locked->username, 'email' => $locked->email,
                'role' => $isGuru ? 'GURU' : 'SISWA',
            ]);
        }, 3);

        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->editingRole = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.']);
        session()->flash('message', 'Data pengguna berhasil diperbarui.');
    }

    public function openResetPasswordModal(string $userId): void
    {
        $this->authorizeUserManagement();
        $user = $this->manageableUserQuery()->whereKey($userId)->firstOrFail();
        $this->resetPasswordUserId = (string) $user->id;
        $this->resetPassword = '';
        $this->resetValidation();
        $this->showResetPasswordModal = true;
    }

    public function resetUserPassword(): void
    {
        $this->authorizeUserManagement();
        $validated = $this->validate(['resetPassword' => ['required','string','min:14']]);
        $userId = (string) $this->resetPasswordUserId;

        DB::transaction(function () use ($userId, $validated): void {
            $user = $this->manageableUserQuery()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $user->password = Hash::make($validated['resetPassword']);
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
            $user->save();

            app(AuditService::class)->log('USER_PASSWORD_RESET', 'User', $user->id, null, [
                'username' => $user->username,
                'role' => $user->roles()->value('name'),
            ]);
        }, 3);

        $this->showResetPasswordModal = false;
        $this->resetPasswordUserId = null;
        $this->resetPassword = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Password pengguna berhasil direset.']);
        session()->flash('message', 'Password pengguna berhasil direset.');
    }

    public function openAccountStatusModal(string $userId, bool $activate): void
    {
        $this->authorizeUserManagement();
        $user = $this->manageableUserQuery()->whereKey($userId)->firstOrFail();

        $this->statusUserId = (string) $user->id;
        $this->statusTarget = $activate;
        $this->statusReason = '';
        $this->resetValidation();
        $this->showAccountStatusModal = true;
    }

    public function updateAccountStatus(): void
    {
        $this->authorizeUserManagement();
        $validated = $this->validate([
            'statusUserId' => ['required', 'uuid'],
            'statusTarget' => ['required', 'boolean'],
            'statusReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = $this->manageableUserQuery()
                ->whereKey((string) $validated['statusUserId'])
                ->lockForUpdate()
                ->firstOrFail();

            $targetActive = (bool) $validated['statusTarget'];
            if ((bool) $user->is_active === $targetActive) {
                return;
            }

            $oldValues = [
                'is_active' => (bool) $user->is_active,
                'username' => $user->username,
                'role' => $user->roles()->value('name'),
            ];

            $user->is_active = $targetActive;
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
            $user->save();

            if (!$targetActive) {
                // Revocation is immediate for bearer tokens. Web sessions are denied
                // by EnsureActiveAccount on their next request.
                $user->tokens()->delete();
            }

            app(AuditService::class)->log(
                $targetActive ? 'USER_REACTIVATED' : 'USER_DEACTIVATED',
                'User',
                $user->id,
                $oldValues,
                [
                    'is_active' => $targetActive,
                    'username' => $user->username,
                    'role' => $user->roles()->value('name'),
                    'reason' => trim($validated['statusReason']),
                ]
            );
        }, 3);

        $this->showAccountStatusModal = false;
        $this->statusUserId = null;
        $this->statusTarget = null;
        $this->statusReason = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Status akun berhasil diperbarui.']);
        session()->flash('message', 'Status akun berhasil diperbarui.');
    }

    public function updatingStatusFilter(string $value): void
    {
        abort_unless(in_array($value, ['', 'active', 'inactive'], true), 422);
        $this->resetPage();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function updatingRoleFilter(string $value): void
    {
        abort_unless($value === '' || in_array($value, ['GURU', 'SISWA'], true), 422);
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizeUserManagement();
        $users = $this->manageableUserQuery()
            ->with(['roles','teacherProfile','studentProfile'])
            ->when($this->search, function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter, fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $this->roleFilter)))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()->paginate(15);
        $roles = Role::query()->whereIn('name', ['GURU','SISWA'])->orderBy('label')->get();

        return view('livewire.admin.user-management', ['users' => $users, 'roles' => $roles])->layout('layouts.app');
    }
}
