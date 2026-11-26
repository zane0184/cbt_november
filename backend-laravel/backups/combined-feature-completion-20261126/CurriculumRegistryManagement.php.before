<?php

namespace App\Livewire\Admin;

use App\Models\CurriculumReferenceSource;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CurriculumRegistryManagement extends Component
{
    public string $selectedRegistrationId = '';
    public string $selectedSubjectId = '';
    public string $selectedStructureId = '';

    private function authorizeRegistry(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('curriculum.registry.manage'),
            403,
            'Akses ditolak. Hanya Admin Madrasah berizin yang dapat mendaftarkan versi kurikulum dan memetakan mata pelajaran lokal.'
        );
    }

    public function mount(): void
    {
        $this->authorizeRegistry();
    }

    public function registerVersion(string $versionId): void
    {
        $this->authorizeRegistry();
        $tenantId = auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $version = MultiCurriculumVersion::query()
            ->where('tenant_id', $systemTenantId)
            ->whereKey($versionId)
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->with('source')
            ->firstOrFail();

        if (!$version->source
            || !in_array($version->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
            throw ValidationException::withMessages([
                'selectedRegistrationId' => 'Sumber versi kurikulum belum melewati verifikasi metadata.',
            ]);
        }

        DB::transaction(function () use ($tenantId, $systemTenantId, $version): void {
            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('curriculum_owner_id', $systemTenantId)
                ->lockForUpdate()
                ->first();

            if ($registration) {
                if ($registration->status !== 'ACTIVE') {
                    $before = $registration->only(['status', 'registered_at']);
                    $registration->update([
                        'status' => 'ACTIVE',
                        'registered_at' => now(),
                    ]);
                    app(AuditService::class)->log(
                        'CURRICULUM_VERSION_REREGISTERED',
                        'TenantCurriculumRegistration',
                        $registration->id,
                        $before,
                        ['status' => 'ACTIVE', 'registered_at' => now()->toISOString()]
                    );
                }

                $this->selectedRegistrationId = $registration->id;
                return;
            }

            $registration = TenantCurriculumRegistration::query()->create([
                'tenant_id' => $tenantId,
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenantId,
                'status' => 'ACTIVE',
                'registered_at' => now(),
            ]);

            app(AuditService::class)->log(
                'CURRICULUM_VERSION_REGISTERED',
                'TenantCurriculumRegistration',
                $registration->id,
                null,
                [
                    'tenant_id' => $tenantId,
                    'multi_curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $systemTenantId,
                    'version_code' => $version->version_code,
                ]
            );

            $this->selectedRegistrationId = $registration->id;
        }, 3);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Versi kurikulum resmi berhasil didaftarkan pada instalasi madrasah ini.']);
    }

    public function mapSubject(): void
    {
        $this->authorizeRegistry();
        $tenantId = auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $this->validate([
            'selectedRegistrationId' => ['required', 'uuid'],
            'selectedSubjectId' => ['required', 'uuid'],
            'selectedStructureId' => ['required', 'uuid'],
        ]);

        DB::transaction(function () use ($tenantId, $systemTenantId): void {
            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->whereKey($this->selectedRegistrationId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($registration->curriculum_owner_id !== $systemTenantId) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Registrasi bukan berasal dari registry kurikulum resmi sistem.',
                ]);
            }

            $subject = Subject::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->selectedSubjectId)
                ->firstOrFail();

            $structure = MultiCurriculumStructure::query()
                ->where('tenant_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('node_type', 'SUBJECT')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->whereKey($this->selectedStructureId)
                ->with('source')
                ->firstOrFail();

            if (!$structure->source
                || !in_array($structure->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                throw ValidationException::withMessages([
                    'selectedStructureId' => 'Sumber struktur mata pelajaran belum diverifikasi.',
                ]);
            }

            $before = TenantCurriculumSubjectMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('subject_id', $subject->id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('multi_curriculum_structure_id', $structure->id)
                ->first();

            $mapping = TenantCurriculumSubjectMapping::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'multi_curriculum_structure_id' => $structure->id,
                ],
                [
                    'tenant_curriculum_registration_id' => $registration->id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'multi_curriculum_structure_id' => $structure->id,
                    'status' => 'ACTIVE',
                    'effective_from' => $structure->effective_from,
                    'effective_until' => $structure->effective_until,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'local_subject_code' => $subject->code,
                        'official_subject_code' => $structure->metadata['subject_code'] ?? $structure->code,
                        'mapping_method' => 'ADMIN_CONFIRMED',
                    ],
                ]
            );

            app(AuditService::class)->log(
                'CURRICULUM_SUBJECT_MAPPED',
                'TenantCurriculumSubjectMapping',
                $mapping->id,
                $before?->only(['multi_curriculum_structure_id', 'status']),
                [
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'multi_curriculum_structure_id' => $structure->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                ]
            );
        }, 3);

        $this->reset(['selectedSubjectId', 'selectedStructureId']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Pemetaan mata pelajaran lokal berhasil disimpan.']);
    }

    public function retireMapping(string $mappingId): void
    {
        $this->authorizeRegistry();
        $tenantId = auth()->user()->tenant_id;

        $mapping = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($mappingId)
            ->firstOrFail();

        $before = $mapping->only(['status', 'effective_until']);
        $mapping->update([
            'status' => 'INACTIVE',
            'effective_until' => now()->toDateString(),
        ]);

        app(AuditService::class)->log(
            'CURRICULUM_SUBJECT_MAPPING_RETIRED',
            'TenantCurriculumSubjectMapping',
            $mapping->id,
            $before,
            ['status' => 'INACTIVE', 'effective_until' => now()->toDateString()]
        );

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Pemetaan dinonaktifkan. Histori pemetaan tetap dipertahankan.']);
    }

    public function render()
    {
        $this->authorizeRegistry();
        $tenantId = auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $versions = MultiCurriculumVersion::query()
            ->where('tenant_id', $systemTenantId)
            ->where('is_official', true)
            ->with('source')
            ->orderByDesc('effective_year')
            ->orderByDesc('created_at')
            ->get();

        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->with(['version.source', 'owner'])
            ->orderByDesc('registered_at')
            ->get();

        if ($this->selectedRegistrationId === '') {
            $this->selectedRegistrationId = (string) ($registrations->firstWhere('status', 'ACTIVE')?->id ?? '');
        }

        $selectedRegistration = $registrations->firstWhere('id', $this->selectedRegistrationId);
        $subjects = Subject::query()->where('tenant_id', $tenantId)->orderBy('name')->get();

        $subjectMappings = collect();
        $availableSubjectNodes = collect();
        if ($selectedRegistration && $selectedRegistration->status === 'ACTIVE') {
            $subjectMappings = TenantCurriculumSubjectMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('tenant_curriculum_registration_id', $selectedRegistration->id)
                ->with('subject', 'curriculumStructure.source')
                ->orderBy('created_at')
                ->get();

            $availableSubjectNodes = MultiCurriculumStructure::query()
                ->where('tenant_id', $selectedRegistration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $selectedRegistration->multi_curriculum_version_id)
                ->where('node_type', 'SUBJECT')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->with('source')
                ->orderBy('sort_order')
                ->get();
        }

        $sources = CurriculumReferenceSource::query()
            ->visibleToTenant($tenantId)
            ->orderBy('issuer')
            ->orderBy('document_number')
            ->get();

        return view('livewire.admin.curriculum-registry-management', compact(
            'versions',
            'registrations',
            'selectedRegistration',
            'subjects',
            'subjectMappings',
            'availableSubjectNodes',
            'sources'
        ))->layout('layouts.app');
    }
}
