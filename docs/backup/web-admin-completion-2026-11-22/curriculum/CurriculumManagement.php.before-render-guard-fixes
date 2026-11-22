<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\LearningOutcome;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\QuestionMultiCurriculumMapping;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Services\CurriculumRegistrationService;
use App\Services\CurriculumStructureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CurriculumManagement extends Component
{
    use WithPagination;

    public string $activeTab = 'catalog';
    public string $search = '';
    public string $subjectFilter = '';
    public ?string $selectedAcademicYearId = null;
    public ?string $selectedCurriculumId = null;
    public ?string $selectedVersionId = null;
    public ?string $registerVersionId = null;

    public string $curriculumCode = '';
    public string $curriculumName = '';
    public string $curriculumDescription = '';
    public string $curriculumReason = '';

    public string $versionCode = '';
    public string $versionTitle = '';
    public ?string $versionEffectiveYear = null;
    public string $versionReason = '';

    public string $registrationReason = '';

    public ?string $nodeParentId = null;
    public string $nodeType = 'FASE';
    public string $nodeCode = '';
    public string $nodeTitle = '';
    public int $nodeSortOrder = 0;
    public string $nodeReason = '';

    public bool $showEditNodeModal = false;
    public ?string $editNodeId = null;
    public ?string $editNodeParentId = null;
    public string $editNodeType = '';
    public string $editNodeCode = '';
    public string $editNodeTitle = '';
    public int $editNodeSortOrder = 0;
    public string $editNodeReason = '';

    public ?string $outcomeStructureId = null;
    public string $outcomeCode = '';
    public string $outcomeType = 'CP';
    public string $outcomeDescription = '';
    public string $outcomeReason = '';

    public bool $showEditOutcomeModal = false;
    public ?string $editOutcomeId = null;
    public string $editOutcomeCode = '';
    public string $editOutcomeType = '';
    public string $editOutcomeDescription = '';
    public string $editOutcomeReason = '';

    public bool $showArchiveModal = false;
    public string $archiveType = '';
    public ?string $archiveId = null;
    public string $archiveReason = '';

    // Legacy Kurikulum Merdeka CP/TP compatibility surface used by existing Guru Bank Soal.
    public bool $showCreateTpModal = false;
    public string $selectedOutcomeId = '';
    public string $tpCode = '';
    public string $tpTitle = '';
    public string $tpDescription = '';
    public string $tpReason = '';

    public function mount(): void
    {
        $this->authorizeCurriculumView();
        $activeYear = AcademicYear::query()->where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)->first();
        $this->selectedAcademicYearId = $activeYear?->id;
    }

    private function authorizeCurriculumView(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->is_active
                && $user->roles()->count() === 1
                && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $user->hasPermission('curriculum.view'),
            403,
            'Akses ditolak. Diperlukan satu role Admin Madrasah aktif dan permission curriculum.view.'
        );
    }

    private function authorizeCurriculumManagement(): void
    {
        $this->authorizeCurriculumView();
        abort_unless(auth()->user()->hasPermission('curriculum.manage'), 403,
            'Akses ditolak. Diperlukan permission curriculum.manage.');
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['catalog', 'registrations', 'structure', 'history', 'legacy'], true), 422);
        $this->activeTab = $tab;
    }

    public function selectCurriculum(string $curriculumId): void
    {
        $this->authorizeCurriculumView();
        $this->visibleCurriculum($curriculumId);
        $this->selectedCurriculumId = $curriculumId;
        $this->selectedVersionId = null;
        $this->activeTab = 'catalog';
    }

    public function selectVersion(string $versionId): void
    {
        $this->authorizeCurriculumView();
        $tenantId = (string) auth()->user()->tenant_id;
        $version = MultiCurriculumVersion::withoutGlobalScopes()->whereIn('tenant_id', [Tenant::SYSTEM_TENANT_ID, $tenantId])
            ->whereNull('deleted_at')->whereKey($versionId)->firstOrFail();
        $this->selectedCurriculumId = $version->multi_curriculum_id;
        $this->selectedVersionId = $version->id;
        $this->activeTab = 'structure';
    }

    private function visibleCurriculum(string $curriculumId): MultiCurriculum
    {
        $tenantId = (string) auth()->user()->tenant_id;
        return MultiCurriculum::withoutGlobalScopes()->whereIn('tenant_id', [Tenant::SYSTEM_TENANT_ID, $tenantId])
            ->whereNull('deleted_at')->whereKey($curriculumId)->firstOrFail();
    }

    public function createCurriculum(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'curriculumCode' => ['required', 'string', 'min:2', 'max:40'],
            'curriculumName' => ['required', 'string', 'min:3', 'max:160'],
            'curriculumDescription' => ['nullable', 'string', 'max:4000'],
            'curriculumReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $curriculum = app(CurriculumRegistrationService::class)->createTenantCurriculum(
            (string) auth()->user()->tenant_id, (string) auth()->id(),
            $data['curriculumCode'], $data['curriculumName'], $data['curriculumDescription'] ?: null,
            $data['curriculumReason']
        );

        $this->selectedCurriculumId = $curriculum->id;
        $this->curriculumCode = $this->curriculumName = $this->curriculumDescription = $this->curriculumReason = '';
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Kurikulum madrasah berhasil dibuat.');
    }

    public function createVersion(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'selectedCurriculumId' => ['required', 'uuid'],
            'versionCode' => ['required', 'string', 'min:1', 'max:40'],
            'versionTitle' => ['required', 'string', 'min:3', 'max:160'],
            'versionEffectiveYear' => ['nullable', 'integer', 'between:2020,2100'],
            'versionReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $version = app(CurriculumRegistrationService::class)->createTenantVersion(
            (string) auth()->user()->tenant_id, (string) auth()->id(),
            $data['selectedCurriculumId'], $data['versionCode'], $data['versionTitle'],
            $data['versionEffectiveYear'] !== '' ? (int) $data['versionEffectiveYear'] : null,
            $data['versionReason']
        );

        $this->selectedVersionId = $version->id;
        $this->versionCode = $this->versionTitle = $this->versionReason = '';
        $this->versionEffectiveYear = null;
        $this->activeTab = 'structure';
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Versi kurikulum berhasil dibuat. Lengkapi struktur dan capaian sebelum registrasi.');
    }

    public function registerVersion(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'selectedAcademicYearId' => ['required', 'uuid'],
            'registerVersionId' => ['required', 'uuid'],
            'registrationReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        app(CurriculumRegistrationService::class)->registerVersion(
            (string) auth()->user()->tenant_id, (string) auth()->id(),
            $data['selectedAcademicYearId'], $data['registerVersionId'], $data['registrationReason']
        );

        $this->registerVersionId = null;
        $this->registrationReason = '';
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Versi kurikulum berhasil diregistrasikan pada tahun pelajaran.');
    }

    public function deactivateRegistration(string $registrationId): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'registrationReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        app(CurriculumRegistrationService::class)->deactivateRegistration(
            (string) auth()->user()->tenant_id, (string) auth()->id(), $registrationId, $data['registrationReason']
        );
        $this->registrationReason = '';
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Registrasi dinonaktifkan. Riwayat tetap disimpan.');
    }

    public function createNode(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'selectedVersionId' => ['required', 'uuid'],
            'nodeParentId' => ['nullable', 'uuid'],
            'nodeType' => ['required', 'string', 'min:2', 'max:50'],
            'nodeCode' => ['required', 'string', 'min:1', 'max:60'],
            'nodeTitle' => ['required', 'string', 'min:2', 'max:200'],
            'nodeSortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
            'nodeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $node = app(CurriculumStructureService::class)->createNode(
            (string) auth()->user()->tenant_id, (string) auth()->id(), $data['selectedVersionId'],
            $data['nodeParentId'] ?: null, $data['nodeType'], $data['nodeCode'], $data['nodeTitle'],
            (int) $data['nodeSortOrder'], $data['nodeReason']
        );

        $this->selectedStructureId = $node->id;
        $this->nodeParentId = null;
        $this->nodeCode = $this->nodeTitle = $this->nodeReason = '';
        $this->nodeSortOrder = 0;
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Node struktur berhasil ditambahkan.');
    }

    public function openEditNode(string $nodeId): void
    {
        $this->authorizeCurriculumManagement();
        $node = MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', auth()->user()->tenant_id)
            ->whereNull('deleted_at')->whereKey($nodeId)->firstOrFail();
        $this->selectedVersionId = $node->multi_curriculum_version_id;
        $this->editNodeId = $node->id;
        $this->editNodeParentId = $node->parent_id;
        $this->editNodeType = $node->node_type;
        $this->editNodeCode = $node->code;
        $this->editNodeTitle = $node->title;
        $this->editNodeSortOrder = (int) $node->sort_order;
        $this->editNodeReason = '';
        $this->showEditNodeModal = true;
        $this->resetValidation();
    }

    public function updateNode(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'editNodeId' => ['required', 'uuid'],
            'editNodeParentId' => ['nullable', 'uuid'],
            'editNodeType' => ['required', 'string', 'min:2', 'max:50'],
            'editNodeCode' => ['required', 'string', 'min:1', 'max:60'],
            'editNodeTitle' => ['required', 'string', 'min:2', 'max:200'],
            'editNodeSortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
            'editNodeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        app(CurriculumStructureService::class)->updateNode(
            (string) auth()->user()->tenant_id, (string) auth()->id(), $data['editNodeId'],
            $data['editNodeParentId'] ?: null, $data['editNodeType'], $data['editNodeCode'], $data['editNodeTitle'],
            (int) $data['editNodeSortOrder'], $data['editNodeReason']
        );
        $this->showEditNodeModal = false;
        $this->editNodeId = null;
        session()->flash('curriculumMessage', 'Node struktur berhasil diperbarui.');
    }

    public function openEditOutcome(string $outcomeId): void
    {
        $this->authorizeCurriculumManagement();
        $outcome = MultiLearningOutcome::withoutGlobalScopes()->where('tenant_id', auth()->user()->tenant_id)
            ->whereNull('deleted_at')->whereKey($outcomeId)->firstOrFail();
        $this->selectedVersionId = $outcome->multi_curriculum_version_id;
        $this->editOutcomeId = $outcome->id;
        $this->editOutcomeCode = $outcome->code;
        $this->editOutcomeType = $outcome->outcome_type;
        $this->editOutcomeDescription = $outcome->description;
        $this->editOutcomeReason = '';
        $this->showEditOutcomeModal = true;
        $this->resetValidation();
    }

    public function updateOutcome(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'editOutcomeId' => ['required', 'uuid'],
            'editOutcomeCode' => ['required', 'string', 'min:1', 'max:60'],
            'editOutcomeType' => ['required', 'string', 'min:2', 'max:50'],
            'editOutcomeDescription' => ['required', 'string', 'min:10', 'max:10000'],
            'editOutcomeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        app(CurriculumStructureService::class)->updateOutcome(
            (string) auth()->user()->tenant_id, (string) auth()->id(), $data['editOutcomeId'],
            $data['editOutcomeCode'], $data['editOutcomeType'], $data['editOutcomeDescription'], $data['editOutcomeReason']
        );
        $this->showEditOutcomeModal = false;
        $this->editOutcomeId = null;
        session()->flash('curriculumMessage', 'Capaian pembelajaran berhasil diperbarui.');
    }

    public function createOutcome(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'selectedVersionId' => ['required', 'uuid'],
            'outcomeStructureId' => ['required', 'uuid'],
            'outcomeCode' => ['required', 'string', 'min:1', 'max:60'],
            'outcomeType' => ['required', 'string', 'min:2', 'max:50'],
            'outcomeDescription' => ['required', 'string', 'min:10', 'max:10000'],
            'outcomeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        app(CurriculumStructureService::class)->createOutcome(
            (string) auth()->user()->tenant_id, (string) auth()->id(), $data['selectedVersionId'],
            $data['outcomeStructureId'], $data['outcomeCode'], $data['outcomeType'],
            $data['outcomeDescription'], $data['outcomeReason']
        );

        $this->outcomeCode = $this->outcomeDescription = $this->outcomeReason = '';
        $this->resetValidation();
        session()->flash('curriculumMessage', 'Capaian pembelajaran berhasil ditambahkan.');
    }

    public function openArchiveModal(string $type, string $id): void
    {
        $this->authorizeCurriculumManagement();
        abort_unless(in_array($type, ['node', 'outcome'], true), 422);
        $this->archiveType = $type;
        $this->archiveId = $id;
        $this->archiveReason = '';
        $this->showArchiveModal = true;
        $this->resetValidation();
    }

    public function confirmArchive(): void
    {
        $this->authorizeCurriculumManagement();
        $data = $this->validate([
            'archiveType' => ['required', Rule::in(['node', 'outcome'])],
            'archiveId' => ['required', 'uuid'],
            'archiveReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($data['archiveType'] === 'node') {
            app(CurriculumStructureService::class)->archiveNode(
                (string) auth()->user()->tenant_id, (string) auth()->id(), $data['archiveId'], $data['archiveReason']
            );
        } else {
            app(CurriculumStructureService::class)->archiveOutcome(
                (string) auth()->user()->tenant_id, (string) auth()->id(), $data['archiveId'], $data['archiveReason']
            );
        }

        $this->showArchiveModal = false;
        $this->archiveId = null;
        $this->archiveType = '';
        $this->archiveReason = '';
        session()->flash('curriculumMessage', 'Data berhasil diarsipkan tanpa menghapus histori.');
    }

    public function openCreateTpModal(): void
    {
        $this->authorizeCurriculumManagement();
        $this->reset(['selectedOutcomeId', 'tpCode', 'tpTitle', 'tpDescription', 'tpReason']);
        $this->resetValidation();
        $this->showCreateTpModal = true;
    }

    public function createTp(): void
    {
        $this->authorizeCurriculumManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedOutcomeId' => ['required', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $tenantId)],
            'tpCode' => ['required', 'string', 'max:50'],
            'tpTitle' => ['required', 'string', 'max:255'],
            'tpDescription' => ['nullable', 'string', 'max:10000'],
            'tpReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $outcome = LearningOutcome::query()->where('tenant_id', auth()->user()->tenant_id)
                ->lockForUpdate()->findOrFail($data['selectedOutcomeId']);
            $objective = $outcome->objectives()->create([
                'code' => $data['tpCode'],
                'title' => $data['tpTitle'],
                'description' => $data['tpDescription'],
                'status' => 'ACTIVE',
                'created_by' => auth()->id(),
            ]);
            app(\App\Services\AuditService::class)->log('LEARNING_OBJECTIVE_CREATED', 'LearningObjective', $objective->id, null, [
                'learning_outcome_id' => $outcome->id,
                'code' => $objective->code,
                'reason' => trim($data['tpReason']),
            ]);
        });

        $this->showCreateTpModal = false;
        session()->flash('message', "Tujuan Pembelajaran (TP) {$data['tpCode']} berhasil ditambahkan.");
    }

    public function updatingSearch(): void
    {
        $this->resetPage('legacyOutcomesPage');
    }

    public function updatingSubjectFilter(): void
    {
        $this->resetPage('legacyOutcomesPage');
    }

    public function render()
    {
        $this->authorizeCurriculumView();
        $tenantId = (string) auth()->user()->tenant_id;
        $systemTenantId = Tenant::SYSTEM_TENANT_ID;

        $academicYears = AcademicYear::query()->where('tenant_id', $tenantId)->orderByDesc('name')->get();
        $curricula = MultiCurriculum::withoutGlobalScopes()
            ->whereIn('tenant_id', [$systemTenantId, $tenantId])->whereNull('deleted_at')
            ->with(['versions' => fn ($query) => $query->whereNull('deleted_at')->orderByDesc('effective_year')->orderBy('version_code')])
            ->orderBy('name')->get();

        $selectedCurriculum = $this->selectedCurriculumId
            ? $curricula->firstWhere('id', $this->selectedCurriculumId)
            : null;
        $selectedCurriculumVersions = $selectedCurriculum?->versions ?? collect();

        $availableVersions = MultiCurriculumVersion::withoutGlobalScopes()
            ->whereIn('tenant_id', [$systemTenantId, $tenantId])
            ->where('status', 'ACTIVE')->whereNull('deleted_at')
            ->with('curriculum')
            ->whereHas('curriculum', fn ($query) => $query->whereIn('tenant_id', [$systemTenantId, $tenantId])
                ->where('status', 'ACTIVE')->whereNull('deleted_at'))
            ->orderByDesc('effective_year')->orderBy('version_code')->get();

        $registrations = $this->selectedAcademicYearId
            ? TenantCurriculumRegistration::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)->where('academic_year_id', $this->selectedAcademicYearId)
                ->with(['version.curriculum', 'academicYear', 'owner'])
                ->orderByDesc('registered_at')->get()
            : collect();

        $history = TenantCurriculumRegistration::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['version.curriculum', 'academicYear', 'owner'])
            ->orderByDesc('updated_at')->paginate(20, ['*'], 'curriculumHistoryPage');

        $selectedVersion = $this->selectedVersionId
            ? MultiCurriculumVersion::withoutGlobalScopes()
                ->whereIn('tenant_id', [$systemTenantId, $tenantId])->whereNull('deleted_at')
                ->with('curriculum')->whereKey($this->selectedVersionId)->first()
            : null;

        $structures = $selectedVersion
            ? MultiCurriculumStructure::withoutGlobalScopes()
                ->where('tenant_id', $selectedVersion->tenant_id)
                ->where('multi_curriculum_version_id', $selectedVersion->id)->whereNull('deleted_at')
                ->with(['parent', 'outcomes'])->orderBy('sort_order')->orderBy('code')->get()
            : collect();

        $outcomes = $selectedVersion
            ? MultiLearningOutcome::withoutGlobalScopes()
                ->where('tenant_id', $selectedVersion->tenant_id)
                ->where('multi_curriculum_version_id', $selectedVersion->id)->whereNull('deleted_at')
                ->with('structure')->orderBy('code')->get()
            : collect();

        $canEditSelectedVersion = $selectedVersion
            && (string) $selectedVersion->tenant_id === $tenantId
            && !TenantCurriculumRegistration::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $selectedVersion->id)->exists();

        $legacyOutcomes = LearningOutcome::with(['subject', 'phase', 'currentVersion', 'objectives'])
            ->where('tenant_id', $tenantId)
            ->when($this->subjectFilter, fn ($query) => $query->where('subject_id', $this->subjectFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($searchQuery) {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('objectives', function ($objectiveQuery) {
                            $objectiveQuery->where(function ($termQuery) {
                                $termQuery->where('title', 'like', '%' . $this->search . '%')
                                    ->orWhere('code', 'like', '%' . $this->search . '%');
                            });
                        });
                });
            })
            ->latest()->paginate(10, ['*'], 'legacyOutcomesPage');

        $subjects = Subject::query()->where('tenant_id', $tenantId)->orderBy('name')->get();
        $canManageCurriculum = auth()->user()?->hasPermission('curriculum.manage') ?? false;

        return view('livewire.admin.curriculum-management', compact(
            'academicYears', 'curricula', 'selectedCurriculum', 'selectedCurriculumVersions',
            'availableVersions', 'registrations', 'history', 'selectedVersion', 'structures',
            'outcomes', 'canEditSelectedVersion', 'legacyOutcomes', 'subjects', 'canManageCurriculum'
        ))->layout('layouts.app');
    }
}
