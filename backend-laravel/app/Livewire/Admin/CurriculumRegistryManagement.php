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


    /**
     * Copy verified official subject references into this installation's local
     * subject catalog and map each local subject to its phase-specific node.
     * Existing local names and inactive mapping history are never overwritten.
     */
    public function syncOfficialSubjects(): void
    {
        $this->authorizeRegistry();
        $tenantId = (string) auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $this->validate([
            'selectedRegistrationId' => ['required', 'uuid'],
        ]);

        $result = DB::transaction(function () use ($tenantId, $systemTenantId): array {
            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->whereKey($this->selectedRegistrationId)
                ->with('version.source')
                ->lockForUpdate()
                ->firstOrFail();

            $version = $registration->version;
            if ($registration->curriculum_owner_id !== $systemTenantId
                || !$version
                || $version->tenant_id !== $systemTenantId
                || !$version->is_official
                || $version->is_editable
                || !$version->source
                || !in_array($version->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Sinkronisasi hanya menerima versi kurikulum resmi dengan sumber yang sudah diverifikasi.',
                ]);
            }

            $nodes = MultiCurriculumStructure::query()
                ->where('tenant_id', $systemTenantId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'SUBJECT')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->with('source')
                ->orderBy('sort_order')
                ->lockForUpdate()
                ->get();

            if ($nodes->isEmpty()) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Tidak ada katalog mata pelajaran resmi pada versi yang dipilih.',
                ]);
            }

            $subjectsCreated = 0;
            $mappingsCreated = 0;
            $inactiveHistoryPreserved = 0;

            foreach ($nodes as $node) {
                if (!$node->source
                    || !in_array($node->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                    throw ValidationException::withMessages([
                        'selectedRegistrationId' => 'Ada node mata pelajaran yang sumber resminya belum diverifikasi. Sinkronisasi dibatalkan tanpa perubahan parsial.',
                    ]);
                }

                $officialCode = strtoupper(trim((string) ($node->metadata['subject_code'] ?? '')));
                if ($officialCode === '') {
                    throw ValidationException::withMessages([
                        'selectedRegistrationId' => 'Ada node mata pelajaran resmi tanpa subject_code. Sinkronisasi dibatalkan.',
                    ]);
                }

                // An existing mapping is the strongest identity for a phase node.
                // Check it first so local renames never create a second Mapel on a later sync.
                $existingMappings = TenantCurriculumSubjectMapping::query()
                    ->where('tenant_id', $tenantId)
                    ->where('multi_curriculum_version_id', $version->id)
                    ->where('multi_curriculum_structure_id', $node->id)
                    ->lockForUpdate()
                    ->get();

                if ($existingMappings->count() > 1) {
                    throw ValidationException::withMessages([
                        'selectedRegistrationId' => "Node resmi {$node->code} sudah dipetakan ke lebih dari satu Mapel lokal. Audit pemetaan sebelum melanjutkan sinkronisasi.",
                    ]);
                }

                if ($existingMapping = $existingMappings->first()) {
                    $mappedSubject = Subject::withTrashed()
                        ->where('tenant_id', $tenantId)
                        ->whereKey($existingMapping->subject_id)
                        ->first();

                    if (!$mappedSubject || $mappedSubject->trashed()) {
                        throw ValidationException::withMessages([
                            'selectedRegistrationId' => "Pemetaan node {$node->code} menunjuk Mapel lokal yang tidak tersedia atau telah diarsipkan.",
                        ]);
                    }

                    if ($existingMapping->status !== 'ACTIVE') {
                        $inactiveHistoryPreserved++;
                    }
                    continue;
                }

                $localCode = $this->localSubjectCode($officialCode);
                $subject = Subject::withTrashed()
                    ->where('tenant_id', $tenantId)
                    ->where('code', $localCode)
                    ->lockForUpdate()
                    ->first();

                // Reuse a manually-created local subject when its name matches the
                // official title; never create a duplicate merely because its code differs.
                if (!$subject) {
                    $normalizedTitle = $this->normalizeSubjectName($node->title);
                    $candidates = Subject::withTrashed()
                        ->where('tenant_id', $tenantId)
                        ->lockForUpdate()
                        ->get()
                        ->filter(function (Subject $candidate) use ($normalizedTitle): bool {
                            return $this->normalizeSubjectName($candidate->name) === $normalizedTitle
                                || ($candidate->short_name !== null
                                    && $this->normalizeSubjectName($candidate->short_name) === $normalizedTitle);
                        })
                        ->values();

                    if ($candidates->count() > 1) {
                        throw ValidationException::withMessages([
                            'selectedRegistrationId' => "Nama mapel {$node->title} cocok dengan lebih dari satu Mapel lokal. Sinkronisasi dihentikan agar tidak memilih data yang salah.",
                        ]);
                    }

                    $subject = $candidates->first();
                }

                if ($subject && $subject->trashed()) {
                    throw ValidationException::withMessages([
                        'selectedRegistrationId' => "Mapel lokal {$subject->code} pernah diarsipkan. Data tidak dipulihkan otomatis; periksa histori terlebih dahulu.",
                    ]);
                }

                if ($subject
                    && $officialCode === 'SEJARAH_KEBUDAYAAN_ISLAM'
                    && $subject->code === 'SKI'
                    && !in_array(mb_strtolower(trim($subject->name)), ['sejarah kebudayaan islam', 'ski'], true)) {
                    throw ValidationException::withMessages([
                        'selectedRegistrationId' => 'Kode lokal SKI sudah digunakan untuk nama mapel lain. Sinkronisasi dibatalkan agar tidak membuat pemetaan yang keliru.',
                    ]);
                }

                if (!$subject) {
                    $subject = Subject::create([
                        'code' => $localCode,
                        'name' => $node->title,
                        'short_name' => $this->officialSubjectShortName($officialCode, $node->title),
                        'subject_category' => $officialCode === 'MUATAN_LOKAL' ? 'MULOK' : 'WAJIB',
                        'status' => 'ACTIVE',
                    ]);
                    $subjectsCreated++;
                }

                TenantCurriculumSubjectMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $systemTenantId,
                    'multi_curriculum_structure_id' => $node->id,
                    'status' => 'ACTIVE',
                    'effective_from' => $node->effective_from,
                    'effective_until' => $node->effective_until,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'local_subject_code' => $subject->code,
                        'official_subject_code' => $officialCode,
                        'mapping_method' => 'OFFICIAL_CATALOG_SYNC',
                    ],
                ]);
                $mappingsCreated++;
            }

            app(AuditService::class)->log(
                'CURRICULUM_SUBJECT_CATALOG_SYNCED',
                'TenantCurriculumRegistration',
                $registration->id,
                null,
                [
                    'tenant_id' => $tenantId,
                    'curriculum_version_id' => $version->id,
                    'version_code' => $version->version_code,
                    'official_subject_node_count' => $nodes->count(),
                    'local_subjects_created' => $subjectsCreated,
                    'mappings_created' => $mappingsCreated,
                    'inactive_mapping_history_preserved' => $inactiveHistoryPreserved,
                ]
            );

            return [
                'subjects_created' => $subjectsCreated,
                'mappings_created' => $mappingsCreated,
                'inactive_history_preserved' => $inactiveHistoryPreserved,
            ];
        }, 3);

        session()->flash(
            'message',
            "Sinkronisasi katalog resmi selesai: {$result['subjects_created']} mapel lokal baru dan {$result['mappings_created']} pemetaan baru. {$result['inactive_history_preserved']} pemetaan nonaktif tetap dipertahankan."
        );
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Katalog mata pelajaran resmi berhasil disinkronkan ke instalasi madrasah ini.']);
    }

    private function normalizeSubjectName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/\\b(dan)\\b/u', ' ', $normalized) ?? $normalized;
        return preg_replace('/[^\\p{L}\\p{N}]+/u', '', $normalized) ?? '';
    }

    private function localSubjectCode(string $officialCode): string
    {
        // The local Subject.code column is limited to 20 characters.
        if ($officialCode === 'SEJARAH_KEBUDAYAAN_ISLAM') {
            return 'SKI';
        }

        if (strlen($officialCode) > 20) {
            throw ValidationException::withMessages([
                'selectedRegistrationId' => "Kode mapel resmi {$officialCode} melebihi batas kode lokal 20 karakter dan belum memiliki alias aman.",
            ]);
        }

        return $officialCode;
    }

    private function officialSubjectShortName(string $officialCode, string $title): string
    {
        return match ($officialCode) {
            'ALQURAN_HADIS' => 'Al-Qur’an Hadis',
            'AKIDAH_AKHLAK' => 'Akidah Akhlak',
            'SEJARAH_KEBUDAYAAN_ISLAM' => 'SKI',
            'PENDIDIKAN_PANCASILA' => 'P. Pancasila',
            'BAHASA_INDONESIA' => 'B. Indonesia',
            'BAHASA_ARAB' => 'B. Arab',
            'BAHASA_INGGRIS' => 'B. Inggris',
            'SENI_BUDAYA' => 'Seni Budaya',
            'MUATAN_LOKAL' => 'Mulok',
            'KODING_AI' => 'Koding & AI',
            default => mb_substr($title, 0, 30),
        };
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
