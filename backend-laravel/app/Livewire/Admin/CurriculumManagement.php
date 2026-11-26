<?php

namespace App\Livewire\Admin;

use App\Models\CurriculumReferenceSource;
use App\Models\LearningOutcome;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiLearningOutcome;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Models\Subject;
use Livewire\Component;
use Livewire\WithPagination;

class CurriculumManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $subjectFilter = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->tenant_id
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('curriculum.view'),
            403,
            'Akses ditolak. Referensi CP/TP hanya tersedia bagi Admin Madrasah dengan permission curriculum.view.'
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSubjectFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateTpModal(): void
    {
        abort(403, 'Admin Madrasah hanya dapat melihat CP/TP. Pengembangan TP/ATP dilakukan Guru sesuai penugasan.');
    }

    public function createTp(): void
    {
        abort(403, 'Admin Madrasah hanya dapat melihat CP/TP. Pengembangan TP/ATP dilakukan Guru sesuai penugasan.');
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with('version.source')
            ->orderByDesc('registered_at')
            ->get();

        $registrationIds = $registrations->pluck('id')->all();
        $versionIds = $registrations->pluck('multi_curriculum_version_id')->unique()->values()->all();

        // CP is read-only, official and tied to the exact registered framework version.
        // No synthetic or unverified CP is created to fill missing official content.
        $officialCps = MultiLearningOutcome::query()
            ->where('tenant_id', $systemTenantId)
            ->whereIn('multi_curriculum_version_id', $versionIds ?: ['00000000-0000-0000-0000-000000000000'])
            ->where('outcome_type', 'CP')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->where('verification_status', 'ARTIFACT_CHECKSUM_VERIFIED')
            ->whereNotNull('source_checksum')
            ->whereHas('source', fn ($query) => $query
                ->where('verification_status', 'ARTIFACT_CHECKSUM_VERIFIED')
                ->whereNotNull('checksum_sha256')
                ->whereColumn('curriculum_reference_sources.checksum_sha256', 'multi_learning_outcomes.source_checksum'))
            ->with(['structure.parent', 'source', 'version'])
            ->when($this->search, function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')
            ->paginate(10, ['*'], 'cpPage');

        $frameworkNodes = MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->whereIn('multi_curriculum_version_id', $versionIds ?: ['00000000-0000-0000-0000-000000000000'])
            ->whereIn('node_type', ['PANCA_CINTA', 'PM_PRINCIPLE', 'PM_OLAH', 'PM_FRAMEWORK', 'DIMENSI_PROFIL_LULUSAN'])
            ->where('status', 'ACTIVE')
            ->with(['source', 'version'])
            ->orderBy('node_type')
            ->orderBy('sort_order')
            ->get();

        // Teacher-authored TP and ATP are local to this installation and shown
        // read-only to Admin. Their assignment, semester and parent CP remain linked.
        $teacherOutcomes = TenantLearningOutcome::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('tenant_curriculum_registration_id', $registrationIds ?: ['00000000-0000-0000-0000-000000000000'])
            ->whereIn('outcome_type', ['TP', 'ATP'])
            ->where('is_current', true)
            ->with(['parentCp.source', 'parentTp', 'subject', 'assignment.rombel', 'academicYear', 'semester', 'curriculumVersion'])
            ->when($this->subjectFilter, fn ($query) => $query->where('subject_id', $this->subjectFilter))
            ->when($this->search, function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('outcome_type')
            ->orderBy('code')
            ->paginate(10, ['*'], 'tpPage');

        // Preserve visibility of pre-canonical legacy records, but label them
        // clearly so they are never mistaken for verified official CP content.
        $legacyOutcomes = LearningOutcome::query()
            ->with(['subject', 'phase', 'currentVersion', 'objectives'])
            ->when($this->subjectFilter, fn ($query) => $query->where('subject_id', $this->subjectFilter))
            ->when($this->search, function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('objectives', function ($objectiveQuery): void {
                            $objectiveQuery->where('title', 'like', '%' . $this->search . '%')
                                ->orWhere('code', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'legacyPage');

        $subjects = Subject::query()->where('status', 'ACTIVE')->orderBy('name')->get();
        $curriculumSources = CurriculumReferenceSource::query()
            ->where('tenant_id', $systemTenantId)
            ->whereIn('document_number', [
                'KMA 1503 Tahun 2025',
                'Kepdirjen Pendis 6077 Tahun 2025',
                'Kepmendikdasmen 126/P/2025',
                'Permendikdasmen 10 Tahun 2025',
                'Keputusan Kepala BSKAP 046/H/KR/2025',
                'Keputusan Kepala BKPDM 020 Tahun 2026',
                'Kepdirjen Pendis 9941 Tahun 2025',
            ])
            ->orderBy('document_number')
            ->get();

        return view('livewire.admin.curriculum-management', [
            'registrations' => $registrations,
            'officialCps' => $officialCps,
            'frameworkNodes' => $frameworkNodes,
            'teacherOutcomes' => $teacherOutcomes,
            'legacyOutcomes' => $legacyOutcomes,
            'subjects' => $subjects,
            'curriculumSources' => $curriculumSources,
        ])->layout('layouts.app');
    }
}
