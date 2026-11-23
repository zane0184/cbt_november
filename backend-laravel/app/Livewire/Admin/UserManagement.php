<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showCreateModal = false;
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $selectedRole = '';
    public $nip = '';
    public $nik = '';
    public $nisn = '';

    public bool $showEditModal = false;
    public ?string $editingUserId = null;
    public string $editingRole = '';
    public $editName = '';
    public $editUsername = '';
    public $editEmail = '';
    public $editNip = '';
    public $editNik = '';
    public $editNuptk = '';
    public $editNisn = '';
    public $editGender = '';
    public $editPhone = '';
    public $editBirthPlace = '';
    public $editBirthDate = '';
    public $editParentName = '';
    public $editAddress = '';

    public bool $showTemporaryPasswordModal = false;
    public ?string $temporaryPassword = null;

    public function mount(): void
    {
        $this->authorizeUserManagement();
    }

    /** Re-check authorization on every Livewire mutation; mount is not a mutation boundary. */
    private function authorizeUserManagement(): void
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id
            || $user->roles()->count() !== 1 || !$user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
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

            $profile = null;

            if ($this->selectedRole === 'GURU') {
                $profile = TeacherProfile::query()->create([
                    'user_id' => $user->id, 'full_name' => trim($this->name),
                    'nip' => trim($this->nip) !== '' ? trim($this->nip) : null,
                    'nik' => trim($this->nik) !== '' ? trim($this->nik) : null,
                    'employee_identifier' => trim($this->nip) !== ''
                        ? trim($this->nip)
                        : (trim($this->nik) !== '' ? trim($this->nik) : null),
                ]);
            } elseif ($this->selectedRole === 'SISWA') {
                $profile = StudentProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'nisn' => trim($this->nisn),
                ]);
            }

            // Never include password or personal identifiers (NIP/NIK/NISN) in audit payloads.
            app(AuditService::class)->log(
                'CREATE_LOCAL_USER',
                'User',
                (string) $user->id,
                null,
                [
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $this->selectedRole,
                    'profile_type' => $profile ? class_basename($profile) : null,
                ]
            );
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
        $tenantId = auth()->user()->tenant_id;
        $target = User::query()
            ->where('tenant_id', $tenantId)
            ->with(['roles', 'teacherProfile', 'studentProfile'])
            ->whereKey($userId)
            ->firstOrFail();

        $roleNames = $target->roles()->pluck('name');
        if ($roleNames->count() !== 1 || !in_array($roleNames->first(), ['GURU', 'SISWA'], true)) {
            abort(403, 'Hanya akun Guru atau Siswa yang dapat diubah.');
        }

        $profile = $roleNames->first() === 'GURU' ? $target->teacherProfile : $target->studentProfile;
        if (!$profile) {
            abort(409, 'Profil akademik akun tidak tersedia.');
        }

        $this->editingUserId = (string) $target->id;
        $this->editingRole = $roleNames->first();
        $this->editName = (string) $target->name;
        $this->editUsername = (string) ($target->username ?? '');
        $this->editEmail = (string) ($target->email ?? '');
        $this->editGender = (string) ($profile->gender ?? '');
        $this->editPhone = (string) ($profile->phone ?? '');

        if ($roleNames->first() === 'GURU') {
            $this->editNip = (string) ($profile->nip ?? '');
            $this->editNik = (string) ($profile->nik ?? '');
            $this->editNuptk = (string) ($profile->nuptk ?? '');
        } else {
            $this->editNisn = (string) ($profile->nisn ?? '');
            $this->editBirthPlace = (string) ($profile->birth_place ?? '');
            $this->editBirthDate = $profile->birth_date ? substr((string) $profile->birth_date, 0, 10) : '';
            $this->editParentName = (string) ($profile->parent_name ?? '');
            $this->editAddress = (string) ($profile->address ?? '');
        }

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateUserProfile(): void
    {
        $this->authorizeUserManagement();
        $tenantId = auth()->user()->tenant_id;
        $target = User::query()->where('tenant_id', $tenantId)->whereKey($this->editingUserId)->firstOrFail();
        $roleNames = $target->roles()->pluck('name');
        if ($roleNames->count() !== 1 || !in_array($roleNames->first(), ['GURU', 'SISWA'], true)) {
            abort(403, 'Hanya akun Guru atau Siswa yang dapat diubah.');
        }
        $roleName = $roleNames->first();
        $profile = $roleName === 'GURU' ? $target->teacherProfile : $target->studentProfile;
        if (!$profile) {
            abort(409, 'Profil akademik akun tidak tersedia.');
        }

        $rules = [
            'editName' => ['required', 'string', 'max:255'],
            'editUsername' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($target->id)],
            'editEmail' => ['nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($target->id)],
            'editGender' => ['nullable', Rule::in(['M', 'F'])],
            'editPhone' => ['nullable', 'string', 'max:30'],
        ];

        if ($roleName === 'GURU') {
            $rules += [
                'editNip' => ['nullable', 'string', 'max:18',
                    Rule::unique('teacher_profiles', 'nip')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id)],
                'editNik' => ['nullable', 'digits:16',
                    Rule::unique('teacher_profiles', 'nik')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id)],
                'editNuptk' => ['nullable', 'string', 'max:20'],
            ];
        } else {
            $rules += [
                'editNisn' => ['required', 'digits:10',
                    Rule::unique('student_profiles', 'nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($profile->id)],
                'editBirthPlace' => ['nullable', 'string', 'max:100'],
                'editBirthDate' => ['nullable', 'date', 'before_or_equal:today'],
                'editParentName' => ['nullable', 'string', 'max:255'],
                'editAddress' => ['nullable', 'string', 'max:1000'],
            ];
        }

        $this->validate($rules);

        DB::transaction(function () use ($tenantId, $roleName): void {
            $lockedUser = User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->editingUserId)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedRoles = $lockedUser->roles()->pluck('name');
            if ($lockedRoles->count() !== 1 || $lockedRoles->first() !== $roleName) {
                abort(403, 'Role akun berubah; perubahan dibatalkan.');
            }

            $lockedProfile = $roleName === 'GURU'
                ? $lockedUser->teacherProfile()->lockForUpdate()->first()
                : $lockedUser->studentProfile()->lockForUpdate()->first();
            if (!$lockedProfile) {
                abort(409, 'Profil akademik akun tidak tersedia.');
            }

            $changed = [];
            $userValues = [
                'name' => trim((string) $this->editName),
                'username' => strtolower(trim((string) $this->editUsername)),
                'email' => trim((string) $this->editEmail) !== '' ? strtolower(trim((string) $this->editEmail)) : null,
            ];
            foreach ($userValues as $field => $value) {
                if ($lockedUser->{$field} !== $value) $changed[] = $field;
            }
            $lockedUser->fill($userValues)->save();

            $profileValues = [
                'full_name' => trim((string) $this->editName),
                'gender' => $this->editGender !== '' ? $this->editGender : null,
                'phone' => trim((string) $this->editPhone) !== '' ? trim((string) $this->editPhone) : null,
            ];

            if ($roleName === 'GURU') {
                $profileValues += [
                    'nip' => trim((string) $this->editNip) !== '' ? trim((string) $this->editNip) : null,
                    'nik' => trim((string) $this->editNik) !== '' ? trim((string) $this->editNik) : null,
                    'nuptk' => trim((string) $this->editNuptk) !== '' ? trim((string) $this->editNuptk) : null,
                ];
                $profileValues['employee_identifier'] = $profileValues['nip'] ?: $profileValues['nik'];
            } else {
                $profileValues += [
                    'nisn' => trim((string) $this->editNisn),
                    'birth_place' => trim((string) $this->editBirthPlace) !== '' ? trim((string) $this->editBirthPlace) : null,
                    'birth_date' => $this->editBirthDate !== '' ? $this->editBirthDate : null,
                    'parent_name' => trim((string) $this->editParentName) !== '' ? trim((string) $this->editParentName) : null,
                    'address' => trim((string) $this->editAddress) !== '' ? trim((string) $this->editAddress) : null,
                ];
            }

            foreach ($profileValues as $field => $value) {
                if ($lockedProfile->{$field} != $value) $changed[] = $field;
            }
            $lockedProfile->fill($profileValues)->save();

            $changed = array_values(array_unique($changed));
            if ($changed !== []) {
                app(AuditService::class)->log(
                    'UPDATE_LOCAL_USER_PROFILE',
                    'User',
                    (string) $lockedUser->id,
                    ['changed_fields' => $changed],
                    ['changed_fields' => $changed, 'role' => $roleName, 'profile_type' => class_basename($lockedProfile)]
                );
            }
        }, 3);

        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->editingRole = '';
        $this->reset([
            'editName', 'editUsername', 'editEmail', 'editNip', 'editNik', 'editNuptk',
            'editNisn', 'editGender', 'editPhone', 'editBirthPlace', 'editBirthDate',
            'editParentName', 'editAddress',
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Profil akun berhasil diperbarui.']);
        session()->flash('message', 'Profil akun berhasil diperbarui.');
    }


    /**
     * Apply the same parent-first locks as Attempt start. The account row is
     * always locked last: Exam -> Participant -> Attempt -> User.
     */
    private function lockAccountExamLifecycle(string $tenantId, string $userId): array
    {
        $examIds = ExamParticipant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('exam_id')
            ->pluck('exam_id')
            ->unique()
            ->values();

        if ($examIds->isNotEmpty()) {
            Exam::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $examIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        $participants = ExamParticipant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('exam_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $participantIds = $participants->pluck('id')->all();
        $attempts = $participantIds === []
            ? collect()
            : ExamAttempt::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('participant_id', $participantIds)
                ->orderBy('exam_id')
                ->orderBy('participant_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

        return [$participants, $attempts];
    }

    private function prepareAccountLifecycleTransaction(): void
    {
        // A fresh read after the User mutex must see an Attempt that committed
        // while this operation waited for that mutex. READ COMMITTED provides
        // that visibility without taking Attempt locks after the User lock.
        if (DB::connection()->getDriverName() === 'mysql' && DB::transactionLevel() === 0) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        }
    }

    private function hasOfficialActiveAttempt(string $tenantId, string $userId): bool
    {
        return ExamAttempt::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'IN_PROGRESS')
            ->whereHas('participant', function ($query) use ($tenantId, $userId): void {
                $query->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId);
            })
            ->exists();
    }

    private function revokeAccountCredentials(User $user): void
    {
        $user->tokens()->delete();

        // Database sessions are deleted directly when present. File/other
        // session drivers are invalidated by auth_version on their next request.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    public function resetUserPassword(string $userId): void
    {
        $this->authorizeUserManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $this->temporaryPassword = null;
        $this->showTemporaryPasswordModal = false;
        $this->prepareAccountLifecycleTransaction();

        $result = DB::transaction(function () use ($tenantId, $userId): array {
            [$participants, $attempts] = $this->lockAccountExamLifecycle($tenantId, $userId);

            $lockedUser = User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            $roles = $lockedUser->roles()->pluck('name');
            if ($roles->count() !== 1 || !in_array($roles->first(), ['GURU', 'SISWA'], true)) {
                abort(403, 'Reset password hanya tersedia untuk akun Guru atau Siswa dalam madrasah ini.');
            }

            // Re-check after acquiring the User mutex. Attempt start also locks
            // User last and rejects when must_change_password is true.
            if ($this->hasOfficialActiveAttempt($tenantId, $userId)) {
                app(AuditService::class)->log(
                    'RESET_LOCAL_USER_PASSWORD',
                    'User',
                    (string) $lockedUser->id,
                    null,
                    ['outcome' => 'REJECTED', 'reason' => 'ACTIVE_ATTEMPT_IN_PROGRESS']
                );

                return ['status' => 'REJECTED_ACTIVE_ATTEMPT'];
            }

            $temporaryPassword = Str::random(48);
            $lockedUser->password = Hash::make($temporaryPassword);
            $lockedUser->must_change_password = true;
            $lockedUser->auth_version = (int) $lockedUser->auth_version + 1;
            $lockedUser->save();
            $this->revokeAccountCredentials($lockedUser);

            app(AuditService::class)->log(
                'RESET_LOCAL_USER_PASSWORD',
                'User',
                (string) $lockedUser->id,
                null,
                ['outcome' => 'SUCCESS', 'auth_version' => (int) $lockedUser->auth_version]
            );

            return ['status' => 'SUCCESS', 'temporary_password' => $temporaryPassword];
        }, 3);

        if ($result['status'] === 'REJECTED_ACTIVE_ATTEMPT') {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Reset ditolak: siswa masih memiliki Attempt berstatus IN_PROGRESS. Tidak ada data Attempt yang diubah.']);
            return;
        }

        $this->temporaryPassword = $result['temporary_password'];
        $this->showTemporaryPasswordModal = true;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Password sementara dibuat. Salin sekarang; password hanya ditampilkan pada dialog ini.']);
    }

    public function dismissTemporaryPassword(): void
    {
        $this->temporaryPassword = null;
        $this->showTemporaryPasswordModal = false;
    }

    public function deactivateUser(string $userId): void
    {
        $this->authorizeUserManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $this->prepareAccountLifecycleTransaction();

        $result = DB::transaction(function () use ($tenantId, $userId): array {
            [$participants, $attempts] = $this->lockAccountExamLifecycle($tenantId, $userId);
            $lockedUser = User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            $roles = $lockedUser->roles()->pluck('name');
            if ($roles->count() !== 1 || !in_array($roles->first(), ['GURU', 'SISWA'], true)) {
                abort(403, 'Penonaktifan hanya tersedia untuk akun Guru atau Siswa dalam madrasah ini.');
            }

            if (!(bool) $lockedUser->is_active) {
                return ['status' => 'UNCHANGED'];
            }

            if ($this->hasOfficialActiveAttempt($tenantId, $userId)) {
                app(AuditService::class)->log('DEACTIVATE_LOCAL_USER', 'User', (string) $lockedUser->id, null, [
                    'outcome' => 'REJECTED', 'reason' => 'ACTIVE_ATTEMPT_IN_PROGRESS',
                ]);
                return ['status' => 'REJECTED_ACTIVE_ATTEMPT'];
            }

            $lockedUser->is_active = false;
            $lockedUser->auth_version = (int) $lockedUser->auth_version + 1;
            $lockedUser->save();
            $this->revokeAccountCredentials($lockedUser);

            app(AuditService::class)->log('DEACTIVATE_LOCAL_USER', 'User', (string) $lockedUser->id, null, [
                'outcome' => 'SUCCESS', 'auth_version' => (int) $lockedUser->auth_version,
            ]);

            return ['status' => 'SUCCESS'];
        }, 3);

        $message = match ($result['status']) {
            'SUCCESS' => 'Akun dinonaktifkan. Sesi dan token lama telah dicabut; data akademik tetap dipertahankan.',
            'REJECTED_ACTIVE_ATTEMPT' => 'Penonaktifan ditolak karena masih ada Attempt IN_PROGRESS.',
            default => 'Akun sudah nonaktif.',
        };
        $this->dispatch('notify', ['type' => $result['status'] === 'SUCCESS' ? 'success' : 'error', 'message' => $message]);
    }

    public function activateUser(string $userId): void
    {
        $this->authorizeUserManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $userId): void {
            $lockedUser = User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            $roles = $lockedUser->roles()->pluck('name');
            if ($roles->count() !== 1 || !in_array($roles->first(), ['GURU', 'SISWA'], true)) {
                abort(403, 'Aktivasi hanya tersedia untuk akun Guru atau Siswa dalam madrasah ini.');
            }

            if (!(bool) $lockedUser->is_active) {
                $lockedUser->is_active = true;
                $lockedUser->auth_version = (int) $lockedUser->auth_version + 1;
                $lockedUser->save();

                app(AuditService::class)->log('ACTIVATE_LOCAL_USER', 'User', (string) $lockedUser->id, null, [
                    'outcome' => 'SUCCESS', 'auth_version' => (int) $lockedUser->auth_version,
                ]);
            }
        }, 3);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Akun diaktifkan. Pengguna harus login ulang; sesi/token lama tidak dipulihkan.']);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingRoleFilter(): void { $this->resetPage(); }

    public function render()
    {
        // Re-check role, tenant and permission after hydration before exposing user data.
        $this->authorizeUserManagement();
        $tenantId = auth()->user()->tenant_id;
        $users = User::query()->where('tenant_id', $tenantId)->with(['roles','teacherProfile','studentProfile'])
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
