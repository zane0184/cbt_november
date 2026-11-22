<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;
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
    public $employmentType = '';
    public $nip = '';
    public $nik = '';
    public $nisn = '';
    public $editName = '';
    public $editUsername = '';
    public $editEmail = '';
    public $editEmploymentType = '';
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
            'employmentType' => ['nullable', 'required_if:selectedRole,GURU', Rule::in(['ASN', 'HONORER'])],
            'nip' => ['nullable','digits:18'],
            'nik' => ['nullable','digits:16'],
            'nisn' => ['nullable','digits:10'],
        ];

        if ($this->selectedRole === 'GURU') {
            if ($this->employmentType === 'ASN') {
                $rules['nip'] = ['required', 'digits:18', Rule::unique('teacher_profiles','nip')->where(fn ($q) => $q->where('tenant_id', $tenantId))];
                $rules['nik'] = ['prohibited'];
            } elseif ($this->employmentType === 'HONORER') {
                $rules['nik'] = ['required', 'digits:16', Rule::unique('teacher_profiles','nik')->where(fn ($q) => $q->where('tenant_id', $tenantId))];
                $rules['nip'] = ['prohibited'];
            }
        }
        if ($this->selectedRole === 'SISWA') $rules['nisn'] = ['required','digits:10',Rule::unique('student_profiles','nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))];
        return $rules;
    }

    public function openCreateModal(): void
    {
        $this->authorizeUserManagement();
        $this->reset(['name','username','email','password','selectedRole','employmentType','nip','nik','nisn']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createUser(): void
    {
        $this->authorizeUserManagement();
        $this->validate();
        $tenantId = auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId): void {
            // Serialize manual account creation with bulk imports for this installation.
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')
                ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $this->validate();

            $loginIdentifier = $this->selectedRole === 'GURU'
                ? ($this->employmentType === 'ASN' ? trim($this->nip) : trim($this->nik))
                : trim($this->nisn);
            $usernameConflict = $loginIdentifier !== '' && User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(username) = ?', [strtolower($loginIdentifier)])->exists();
            if ($usernameConflict) {
                throw ValidationException::withMessages([
                    $this->selectedRole === 'GURU' ? ($this->employmentType === 'ASN' ? 'nip' : 'nik') : 'nisn'
                        => 'Identitas login sudah dipakai sebagai username akun lain pada instalasi ini.',
                ]);
            }
            if (trim($this->username) !== '') {
                $usernameIdentityConflict = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where(function ($query): void {
                        $username = strtolower(trim($this->username));
                        $query->whereRaw('LOWER(nip) = ?', [$username])
                            ->orWhereRaw('LOWER(nik) = ?', [$username])
                            ->orWhereRaw('LOWER(employee_identifier) = ?', [$username]);
                    })->exists()
                    || StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                        ->whereRaw('LOWER(nisn) = ?', [strtolower(trim($this->username))])->exists();
                if ($usernameIdentityConflict) {
                    throw ValidationException::withMessages(['username' => 'Username bentrok dengan identitas login Guru/Siswa lain.']);
                }
            }
            if ($this->selectedRole === 'GURU') {
                $identityConflict = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($loginIdentifier): void {
                        $query->where('nip', $loginIdentifier)->orWhere('nik', $loginIdentifier)
                            ->orWhere('employee_identifier', $loginIdentifier);
                    })->exists();
                $studentIdentityConflict = StudentProfile::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)->where('nisn', $loginIdentifier)->exists();
                if ($identityConflict || $studentIdentityConflict) {
                    throw ValidationException::withMessages([
                        $this->employmentType === 'ASN' ? 'nip' : 'nik'
                            => 'Identitas login Guru sudah digunakan oleh akun lain pada instalasi ini.',
                    ]);
                }
            } else {
                $studentIdentityConflict = StudentProfile::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)->where('nisn', $loginIdentifier)->exists();
                $teacherIdentityConflict = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($loginIdentifier): void {
                        $query->where('nip', $loginIdentifier)->orWhere('nik', $loginIdentifier)
                            ->orWhere('employee_identifier', $loginIdentifier);
                    })->exists();
                if ($studentIdentityConflict || $teacherIdentityConflict) {
                    throw ValidationException::withMessages(['nisn' => 'NISN sudah digunakan sebagai identitas login akun lain.']);
                }
            }

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
                $loginIdentifier = $this->employmentType === 'ASN' ? trim($this->nip) : trim($this->nik);
                TeacherProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'employment_type' => $this->employmentType,
                    'nip' => $this->employmentType === 'ASN' ? $loginIdentifier : null,
                    'nik' => $this->employmentType === 'HONORER' ? $loginIdentifier : null,
                    'employee_identifier' => $loginIdentifier,
                ]);
            } elseif ($this->selectedRole === 'SISWA') {
                StudentProfile::query()->create(['user_id' => $user->id, 'full_name' => trim($this->name), 'nisn' => trim($this->nisn)]);
            }

            app(AuditService::class)->log('USER_CREATED', 'User', $user->id, null, [
                'name' => $user->name, 'role' => $this->selectedRole,
                'username' => $user->username,
                'profile_type' => $this->selectedRole === 'GURU' ? 'TeacherProfile' : 'StudentProfile',
                'employment_type' => $this->selectedRole === 'GURU' ? $this->employmentType : null,
                'login_identifier_suffix' => $this->selectedRole === 'GURU'
                    ? substr($this->employmentType === 'ASN' ? trim($this->nip) : trim($this->nik), -4)
                    : substr(trim($this->nisn), -4),
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
        $this->editEmploymentType = $user->teacherProfile?->employment_type ?? '';
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
            'editUsername' => ['nullable','string','min:3','max:80','regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users','username')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id)],
            'editEmail' => ['nullable','email','max:255', Rule::unique('users','email')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->id)],
        ];
        if ($isGuru) {
            $rules['editEmploymentType'] = ['required', Rule::in(['ASN', 'HONORER'])];
            if ($this->editEmploymentType === 'ASN') {
                $rules['editNip'] = ['required', 'digits:18', Rule::unique('teacher_profiles','nip')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->teacherProfile->id)];
                $rules['editNik'] = ['prohibited'];
            } elseif ($this->editEmploymentType === 'HONORER') {
                $rules['editNik'] = ['required', 'digits:16', Rule::unique('teacher_profiles','nik')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->teacherProfile->id)];
                $rules['editNip'] = ['prohibited'];
            }
        } else {
            $rules['editNisn'] = ['required','digits:10', Rule::unique('student_profiles','nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($user->studentProfile->id)];
        }
        $validated = $this->validate($rules);

        DB::transaction(function () use ($userId, $tenantId, $isGuru, $validated, $rules): void {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')
                ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $this->validate($rules);
            $locked = $this->manageableUserQuery()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $editUsername = strtolower(trim($validated['editUsername'] ?? ''));
            $usernameIdentityConflict = $editUsername !== '' && (
                TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('user_id', '<>', $locked->id)
                    ->where(function ($query) use ($editUsername): void {
                        $query->whereRaw('LOWER(nip) = ?', [$editUsername])
                            ->orWhereRaw('LOWER(nik) = ?', [$editUsername])
                            ->orWhereRaw('LOWER(employee_identifier) = ?', [$editUsername]);
                    })->exists()
                || StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('user_id', '<>', $locked->id)
                    ->whereRaw('LOWER(nisn) = ?', [$editUsername])->exists()
            );
            if ($usernameIdentityConflict) {
                throw ValidationException::withMessages(['editUsername' => 'Username bentrok dengan identitas login Guru/Siswa lain.']);
            }
            $oldValues = ['name' => $locked->name, 'username' => $locked->username, 'email' => $locked->email];
            $locked->name = trim($validated['editName']);
            $locked->username = trim($validated['editUsername'] ?? '') !== '' ? strtolower(trim($validated['editUsername'])) : null;
            $locked->email = ($validated['editEmail'] ?? '') !== '' ? strtolower(trim($validated['editEmail'])) : null;
            $locked->save();

            if ($isGuru) {
                $profile = TeacherProfile::query()->where('tenant_id', $tenantId)->where('user_id', $locked->id)->lockForUpdate()->firstOrFail();
                $loginIdentifier = $validated['editEmploymentType'] === 'ASN'
                    ? trim($validated['editNip']) : trim($validated['editNik']);
                $identityConflict = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('id', '<>', $profile->id)
                    ->where(function ($query) use ($loginIdentifier): void {
                        $query->where('nip', $loginIdentifier)->orWhere('nik', $loginIdentifier)
                            ->orWhere('employee_identifier', $loginIdentifier);
                    })->exists();
                $usernameConflict = User::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('id', '<>', $locked->id)->whereRaw('LOWER(username) = ?', [strtolower($loginIdentifier)])->exists();
                $studentIdentityConflict = StudentProfile::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)->where('nisn', $loginIdentifier)->exists();
                if ($identityConflict || $usernameConflict || $studentIdentityConflict) {
                    throw ValidationException::withMessages([
                        $validated['editEmploymentType'] === 'ASN' ? 'editNip' : 'editNik'
                            => 'Identitas login sudah digunakan pada instalasi ini.',
                    ]);
                }
                $oldValues['employment_type'] = $profile->employment_type;
                $oldValues['nip'] = $profile->nip;
                $oldValues['nik'] = $profile->nik;
                $profile->full_name = $locked->name;
                $profile->employment_type = $validated['editEmploymentType'];
                $profile->nip = $validated['editEmploymentType'] === 'ASN' ? trim($validated['editNip']) : null;
                $profile->nik = $validated['editEmploymentType'] === 'HONORER' ? trim($validated['editNik']) : null;
                $profile->employee_identifier = $profile->nip ?: $profile->nik;
                $profile->save();
            } else {
                $profile = StudentProfile::query()->where('tenant_id', $tenantId)->where('user_id', $locked->id)->lockForUpdate()->firstOrFail();
                $nisnConflict = StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('id', '<>', $profile->id)->where('nisn', $validated['editNisn'])->exists();
                $usernameConflict = User::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('id', '<>', $locked->id)->whereRaw('LOWER(username) = ?', [strtolower($validated['editNisn'])])->exists();
                $teacherIdentityConflict = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($validated): void {
                        $query->where('nip', $validated['editNisn'])->orWhere('nik', $validated['editNisn'])
                            ->orWhere('employee_identifier', $validated['editNisn']);
                    })->exists();
                if ($nisnConflict || $usernameConflict || $teacherIdentityConflict) {
                    throw ValidationException::withMessages(['editNisn' => 'NISN sudah digunakan sebagai identitas login akun lain.']);
                }
                $oldValues['nisn'] = $profile->nisn;
                $profile->full_name = $locked->name;
                $profile->nisn = trim($validated['editNisn']);
                $profile->save();
            }

            app(AuditService::class)->log('USER_UPDATED', 'User', $locked->id, $oldValues, [
                'name' => $locked->name, 'username' => $locked->username, 'email' => $locked->email,
                'role' => $isGuru ? 'GURU' : 'SISWA',
                'employment_type' => $isGuru ? $profile->employment_type : null,
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
