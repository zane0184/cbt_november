<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
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
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $selectedRole = '';
    public $nip = '';
    public $nik = '';
    public $nisn = '';

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

            if ($this->selectedRole === 'GURU') {
                TeacherProfile::query()->create([
                    'user_id' => $user->id, 'full_name' => trim($this->name),
                    'nip' => trim($this->nip) !== '' ? trim($this->nip) : null,
                    'nik' => trim($this->nik) !== '' ? trim($this->nik) : null,
                    'employee_identifier' => trim($this->nip) !== ''
                        ? trim($this->nip)
                        : (trim($this->nik) !== '' ? trim($this->nik) : null),
                ]);
            } elseif ($this->selectedRole === 'SISWA') {
                StudentProfile::query()->create(['user_id' => $user->id, 'full_name' => trim($this->name), 'nisn' => trim($this->nisn)]);
            }
        }, 3);

        $createdName = $this->name;
        $this->showCreateModal = false;
        $this->reset(['name','username','email','password','selectedRole','nip','nik','nisn']);
        $this->dispatch('notify', ['type' => 'success', 'message' => "Pengguna {$createdName} berhasil ditambahkan."]);
        session()->flash('message', "Pengguna {$createdName} berhasil ditambahkan.");
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
