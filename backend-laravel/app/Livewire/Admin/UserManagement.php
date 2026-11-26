<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
    public $employmentStatus = '';
    public $nip = '';
    public $nuptk = '';
    public $nik = '';
    public $nisn = '';
    public ?string $oneTimePassword = null;
    public ?string $oneTimePasswordFor = null;
    public bool $showOneTimePasswordModal = false;
    public array $importCredentials = [];
    public ?string $importCredentialsLabel = null;
    public ?string $importBatchId = null;
    public bool $showImportCredentials = false;

    public function mount(): void
    {
        $this->authorizeUserManagement();
        $credentials = session()->pull('import_credentials', []);
        if (is_array($credentials) && $credentials !== []) {
            $this->importCredentials = $credentials;
            $this->importCredentialsLabel = session()->pull('import_credentials_label', 'Akun');
            $this->importBatchId = session()->pull('import_batch_id');
            $this->roleFilter = $this->importCredentialsLabel === 'Guru' ? 'GURU' : 'SISWA';
            $this->showImportCredentials = true;
        } else {
            session()->forget(['import_credentials_label', 'import_batch_id']);
        }
    }

    public function updatedEmploymentStatus($value): void
    {
        if (in_array($value, ['PNS', 'PPPK'], true)) {
            $this->nik = '';
        } elseif ($value === 'HONORER') {
            $this->nip = '';
        } else {
            $this->nip = '';
            $this->nik = '';
        }
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
            'password' => ['required','string','min:8'],
            'selectedRole' => ['required','string',Rule::in($manageableRoles)],
            'employmentStatus' => ['nullable', Rule::in(['PNS', 'PPPK', 'HONORER'])],
            'nip' => ['nullable', 'digits:18'],
            'nuptk' => ['nullable', 'digits:16'],
            'nik' => ['nullable', 'digits:16'],
            'nisn' => ['nullable', 'digits:10'],
        ];

        if ($this->selectedRole === 'GURU') {
            $rules['employmentStatus'] = ['required', Rule::in(['PNS', 'PPPK', 'HONORER'])];
            $rules['nisn'] = ['prohibited'];

            if (in_array($this->employmentStatus, ['PNS', 'PPPK'], true)) {
                $rules['nip'] = [
                    'required',
                    'digits:18',
                    Rule::unique('teacher_profiles', 'nip')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
                ];
                $rules['nik'] = ['prohibited'];
            } elseif ($this->employmentStatus === 'HONORER') {
                $rules['nip'] = ['prohibited'];
                $rules['nik'] = [
                    'required',
                    'digits:16',
                    Rule::unique('teacher_profiles', 'nik')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
                ];
            }

            $rules['nuptk'] = ['nullable', 'digits:16'];
            if (trim((string) $this->nuptk) !== '') {
                $rules['nuptk'][] = Rule::unique('teacher_profiles', 'nuptk')->where(fn ($q) => $q->where('tenant_id', $tenantId));
            }
        }

        if ($this->selectedRole === 'SISWA') {
            $rules['nip'] = ['prohibited'];
            $rules['nik'] = ['prohibited'];
            $rules['nuptk'] = ['prohibited'];
            $rules['employmentStatus'] = ['prohibited'];
            $rules['nisn'] = [
                'required',
                'digits:10',
                Rule::unique('student_profiles', 'nisn')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ];
        }

        return $rules;
    }

    public function openCreateModal(): void
    {
        $this->authorizeUserManagement();
        $this->reset(['name','username','email','password','selectedRole','employmentStatus','nip','nuptk','nik','nisn']);
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
                $nip = trim((string) $this->nip);
                $nuptk = trim((string) $this->nuptk);
                $nik = trim((string) $this->nik);
                $employeeIdentifier = in_array($this->employmentStatus, ['PNS', 'PPPK'], true) ? $nip : $nik;
                $profile = TeacherProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'employment_status' => trim((string) $this->employmentStatus) !== '' ? $this->employmentStatus : null,
                    'nip' => $nip !== '' ? $nip : null,
                    'nuptk' => $nuptk !== '' ? $nuptk : null,
                    'nik' => $nik !== '' ? $nik : null,
                    'employee_identifier' => $employeeIdentifier !== '' ? $employeeIdentifier : null,
                ]);

                app(AuditService::class)->log(
                    'TEACHER_PROFILE_CREATED',
                    'TeacherProfile',
                    $profile->id,
                    null,
                    [
                        'user_id' => $user->id,
                        'employment_status' => $profile->employment_status,
                        'has_nip' => $nip !== '',
                        'has_nik' => $nik !== '',
                        'has_nuptk' => $nuptk !== '',
                    ]
                );
            } elseif ($this->selectedRole === 'SISWA') {
                $profile = StudentProfile::query()->create([
                    'user_id' => $user->id,
                    'full_name' => trim($this->name),
                    'nisn' => trim($this->nisn),
                ]);

                app(AuditService::class)->log(
                    'STUDENT_PROFILE_CREATED',
                    'StudentProfile',
                    $profile->id,
                    null,
                    ['user_id' => $user->id, 'has_nisn' => trim((string) $this->nisn) !== '']
                );
            }

            app(AuditService::class)->log(
                'USER_CREATED',
                'User',
                $user->id,
                null,
                [
                    'role' => $role->name,
                    'profile_type' => $this->selectedRole === 'GURU' ? 'TeacherProfile' : 'StudentProfile',
                    'profile_id' => $profile->id,
                ]
            );
        }, 3);

        $createdName = $this->name;
        $this->showCreateModal = false;
        $this->reset(['name','username','email','password','selectedRole','employmentStatus','nip','nuptk','nik','nisn']);
        $this->dispatch('notify', ['type' => 'success', 'message' => "Pengguna {$createdName} berhasil ditambahkan."]);
        session()->flash('message', "Pengguna {$createdName} berhasil ditambahkan.");
    }

    public function activateUser(string $userId): void
    {
        $this->authorizeUserManagement();
        app(UserAccountService::class)->activate($userId);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Akun berhasil diaktifkan.']);
    }

    public function deactivateUser(string $userId): void
    {
        $this->authorizeUserManagement();
        app(UserAccountService::class)->deactivate($userId);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Akun dinonaktifkan dan sesi aktif dicabut.']);
    }

    public function resetUserPassword(string $userId): void
    {
        $this->authorizeUserManagement();
        $user = User::query()->where('tenant_id', auth()->user()->tenant_id)->whereKey($userId)->firstOrFail();
        $this->oneTimePassword = app(UserAccountService::class)->resetPassword($userId);
        $this->oneTimePasswordFor = $user->name;
        $this->showOneTimePasswordModal = true;
    }

    public function closeOneTimePasswordModal(): void
    {
        $this->oneTimePassword = null;
        $this->oneTimePasswordFor = null;
        $this->showOneTimePasswordModal = false;
    }

    public function downloadImportCredentialsXlsx()
    {
        $this->authorizeUserManagement();
        abort_unless($this->showImportCredentials && $this->importCredentials !== [], 404);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kredensial');
        $headers = ['nama', 'identitas_login', 'jenis_identitas', 'password_awal'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }

        foreach ($this->importCredentials as $index => $credential) {
            $row = $index + 2;
            $values = [
                (string) ($credential['name'] ?? ''),
                (string) ($credential['login_identifier'] ?? ''),
                (string) ($credential['identifier_type'] ?? ''),
                (string) ($credential['password'] ?? ''),
            ];
            foreach ($values as $columnIndex => $value) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1).$row;
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
            }
        }

        $lastRow = count($this->importCredentials) + 1;
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D'.$lastRow)->getNumberFormat()->setFormatCode('@');
        foreach (['A' => 30, 'B' => 24, 'C' => 20, 'D' => 28] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:D'.$lastRow);

        $label = strtolower((string) $this->importCredentialsLabel) === 'guru' ? 'guru' : 'siswa';
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'kredensial-import-'.$label.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function clearImportCredentials(): void
    {
        $this->authorizeUserManagement();
        $this->importCredentials = [];
        $this->importCredentialsLabel = null;
        $this->importBatchId = null;
        $this->showImportCredentials = false;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingRoleFilter(): void { $this->resetPage(); }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;
        $users = User::query()->where('tenant_id', $tenantId)->with(['roles','teacherProfile','studentProfile'])
            ->when($this->search, function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhereHas('teacherProfile', fn ($profile) => $profile
                            ->where('nip', 'like', '%'.$this->search.'%')
                            ->orWhere('nik', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('studentProfile', fn ($profile) => $profile
                            ->where('nisn', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->roleFilter, fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $this->roleFilter)))
            ->latest()->paginate(15);
        $roles = Role::query()->whereIn('name', ['GURU','SISWA'])->orderBy('label')->get();
        return view('livewire.admin.user-management', ['users' => $users, 'roles' => $roles])->layout('layouts.app');
    }
}
