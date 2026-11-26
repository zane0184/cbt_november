<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\AssessmentTaxonomyOption;
use App\Models\Assessment;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Services\ExamSnapshotService;
use App\Services\TeacherPortal\AssessmentCurriculumMappingService;
use App\Services\TeacherPortal\TeacherPortalDataService;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ExamManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public function boot(): void
    {
        $this->assertCanAccess();
    }

    public function mount(): void
    {
        $this->assertCanAccess();
    }

    private function assertCanAccess(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->tenant_id, 403, 'Manajemen ujian hanya dapat diakses Guru sesuai penugasan.');

        // Do not trust a previously loaded role relation after role changes.
        $user->unsetRelation('roles');
        abort_unless($user->hasPermission('exam.manage'), 403, 'Manajemen ujian hanya dapat diakses Guru sesuai penugasan.');

        // Reuse the canonical Portal Guru boundary: exactly one GURU role,
        // persisted tenant identity, and exactly one tenant-local TeacherProfile.
        app(TeacherPortalAccess::class)->profile($user);

        // A valid profile without an active academic assignment is not enough
        // to enter the subject-scoped Guru management screens.
        abort_unless(
            TeacherAccess::assignedSubjectIds($user) !== [],
            403,
            'Manajemen ujian hanya dapat diakses Guru sesuai penugasan.'
        );
    }

    // Modal State
    public $showModal = false;
    public $editingExamId = null;

    public $title = '';
    public $instructions = 'Kerjakan soal dengan jujur dan teliti.';
    public $selectedSubjectId = '';
    public $selectedAssignmentId = '';
    public $activityName = '';
    public $assessmentPurpose = '';
    public $assessmentOrigin = 'INTERNAL';
    public $externalProgramName = '';
    public $selectedAssessmentFunctionOptionId = '';
    public $selectedAssessmentScopeOptionId = '';
    public $selectedInstrumentTypeOptionId = '';
    public $selectedDeliveryModeOptionId = '';
    public array $selectedCpIds = [];
    public array $selectedOutcomeIds = [];
    public $selectedMaterialId = '';
    public $selectedMaterialTopicId = '';
    public $selectedSemesterId = '';
    public $selectedRombelId = '';
    public $confirmAllStudents = false;
    public $durationMinutes = 60;
    public $attemptLimit = 1;
    public $requireToken = true;
    public $shuffleQuestions = true;
    public $shuffleOptions = true;
    public $startAt = '';
    public $endAt = '';

    // Selected Question Version IDs for Exam
    public $selectedQuestionVersionIds = [];

    public function openCreateModal(): void
    {
        $this->assertCanAccess();
        $this->reset([
            'editingExamId', 'title', 'instructions', 'selectedSubjectId',
            'selectedAssignmentId', 'selectedSemesterId', 'selectedRombelId',
            'activityName', 'assessmentPurpose', 'externalProgramName',
            'selectedAssessmentFunctionOptionId', 'selectedAssessmentScopeOptionId',
            'selectedInstrumentTypeOptionId', 'selectedDeliveryModeOptionId',
            'selectedCpIds', 'selectedOutcomeIds', 'selectedMaterialId',
            'selectedMaterialTopicId', 'selectedQuestionVersionIds',
        ]);
        $this->assessmentOrigin = 'INTERNAL';
        $this->durationMinutes = 60;
        $this->attemptLimit = 1;
        $this->startAt = now()->addDay()->startOfHour()->format('Y-m-d\TH:i');
        $this->endAt = now()->addDay()->addHours(2)->startOfHour()->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function openEditModal($examId): void
    {
        $this->assertCanAccess();
        $exam = Exam::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with([
                'assessment.curriculumMappings',
                'assessment.assessmentFunctionOption',
                'assessment.assessmentScopeOption',
                'assessment.instrumentTypeOption',
                'assessment.deliveryModeOption',
                'snapshots',
            ])
            ->findOrFail($examId);
        Gate::authorize('update', $exam);
        abort_unless(TeacherAccess::canManageExam(auth()->user(), $exam), 403, 'Ujian berada di luar penugasan Guru ini.');
        abort_unless($exam->canBeEdited(), 403, 'Ujian yang telah diterbitkan tidak dapat diedit.');

        $assessment = $exam->assessment;
        abort_unless($assessment && $assessment->teacher_subject_assignment_id, 409, 'Asesmen lama belum terikat ke penugasan Guru. Buat asesmen baru dengan konteks kurikulum yang lengkap.');

        $this->editingExamId = $exam->id;
        $this->title = $exam->title;
        $this->instructions = $exam->instructions ?? '';
        $this->selectedAssignmentId = $assessment->teacher_subject_assignment_id;
        $this->selectedSubjectId = $assessment->subject_id;
        $this->selectedSemesterId = $assessment->semester_id;
        $this->selectedRombelId = $assessment->teacherSubjectAssignment?->rombel_id ?? '';
        $this->activityName = $assessment->activity_name ?? '';
        $this->assessmentPurpose = $assessment->description ?? '';
        $this->assessmentOrigin = $assessment->assessment_origin ?? 'INTERNAL';
        $this->externalProgramName = $assessment->external_program_name ?? '';
        $this->selectedAssessmentFunctionOptionId = $assessment->assessment_function_option_id ?? '';
        $this->selectedAssessmentScopeOptionId = $assessment->assessment_scope_option_id ?? '';
        $this->selectedInstrumentTypeOptionId = $assessment->instrument_type_option_id ?? '';
        $this->selectedDeliveryModeOptionId = $assessment->delivery_mode_option_id ?? '';
        $this->selectedCpIds = $assessment->curriculumMappings->pluck('multi_learning_outcome_id')->unique()->values()->all();
        $this->selectedOutcomeIds = $assessment->curriculumMappings->pluck('tenant_learning_outcome_id')->filter()->unique()->values()->all();
        $firstMapping = $assessment->curriculumMappings->first();
        $this->selectedMaterialId = $firstMapping?->material_id ?? '';
        $this->selectedMaterialTopicId = $firstMapping?->material_topic_id ?? '';
        $this->durationMinutes = (int) floor($exam->duration_seconds / 60);
        $this->attemptLimit = $exam->attempt_limit;
        $this->startAt = $exam->start_at?->format('Y-m-d\TH:i');
        $this->endAt = $exam->end_at?->format('Y-m-d\TH:i');
        $this->selectedQuestionVersionIds = $exam->snapshots->pluck('source_question_version_id')->all();
        $this->showModal = true;
    }

    public function updatedSelectedAssignmentId($value): void
    {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('teacher_profile_id', $profile->id)
            ->whereKey($value)
            ->where('status', 'ACTIVE')
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->first();

        $this->selectedSubjectId = $assignment?->subject_id ?? '';
        $this->selectedSemesterId = $assignment?->semester_id ?? '';
        $this->selectedRombelId = $assignment?->rombel_id ?? '';
        $this->selectedCpIds = [];
        $this->selectedOutcomeIds = [];
        $this->selectedMaterialId = '';
        $this->selectedMaterialTopicId = '';
        $this->selectedQuestionVersionIds = [];
    }

    public function updatedSelectedMaterialId(): void
    {
        $this->selectedMaterialTopicId = '';
    }

    public function toggleQuestionSelection($versionId)
    {
        if (in_array($versionId, $this->selectedQuestionVersionIds)) {
            $this->selectedQuestionVersionIds = array_diff($this->selectedQuestionVersionIds, [$versionId]);
        } else {
            $this->selectedQuestionVersionIds[] = $versionId;
        }
    }

    public function createExam()
    {
        return $this->saveExam();
    }

    public function saveExam(): void
    {
        $this->assertCanAccess();

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'selectedAssignmentId' => ['required', 'uuid'],
            'activityName' => ['required', 'string', 'max:255'],
            'assessmentPurpose' => ['required', 'string', 'max:5000'],
            'assessmentOrigin' => ['required', 'in:INTERNAL,EXTERNAL'],
            'externalProgramName' => ['nullable', 'string', 'max:255', 'required_if:assessmentOrigin,EXTERNAL'],
            'selectedAssessmentFunctionOptionId' => ['required', 'uuid'],
            'selectedAssessmentScopeOptionId' => ['required', 'uuid'],
            'selectedInstrumentTypeOptionId' => ['required', 'uuid'],
            'selectedDeliveryModeOptionId' => ['required', 'uuid'],
            'selectedCpIds' => ['required', 'array', 'min:1'],
            'selectedCpIds.*' => ['required', 'uuid', 'distinct'],
            'selectedOutcomeIds' => ['required', 'array', 'min:1'],
            'selectedOutcomeIds.*' => ['required', 'uuid', 'distinct'],
            'selectedMaterialId' => ['nullable', 'uuid'],
            'selectedMaterialTopicId' => ['nullable', 'uuid'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'durationMinutes' => ['required', 'integer', 'min:1', 'max:600'],
            'attemptLimit' => ['required', 'integer', 'min:1', 'max:10'],
            'startAt' => ['required', 'date', 'after_or_equal:now'],
            'endAt' => ['required', 'date', 'after:startAt'],
            'selectedQuestionVersionIds' => ['required', 'array', 'min:1'],
            'selectedQuestionVersionIds.*' => ['required', 'uuid', 'distinct'],
        ]);

        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenantId = (string) $user->tenant_id;

        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('teacher_profile_id', $profile->id)
            ->whereKey($this->selectedAssignmentId)
            ->where('status', 'ACTIVE')
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->with(['subject', 'rombel.gradeLevel.phase', 'academicYear', 'semester', 'curriculumRegistration', 'curriculumVersion.source'])
            ->firstOrFail();

        if (!$assignment->curriculumRegistration
            || $assignment->curriculumRegistration->status !== 'ACTIVE'
            || empty($assignment->curriculum_context_snapshot['subject']['official_structure_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selectedAssignmentId' => 'Penugasan belum memiliki registrasi dan konteks kurikulum resmi yang aktif.',
            ]);
        }

        $startAt = \Illuminate\Support\Carbon::parse($this->startAt);
        $endAt = \Illuminate\Support\Carbon::parse($this->endAt);
        if (($assignment->starts_at && $startAt->toDateString() < $assignment->starts_at->toDateString())
            || ($assignment->ends_at && $endAt->toDateString() > $assignment->ends_at->toDateString())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'startAt' => 'Jadwal ujian harus berada di dalam masa penugasan Guru.',
            ]);
        }

        $taxonomyOwnerId = Tenant::SYSTEM_TENANT_ID;
        $functionOption = AssessmentTaxonomyOption::query()
            ->where('tenant_id', $taxonomyOwnerId)->whereKey($this->selectedAssessmentFunctionOptionId)
            ->where('dimension', 'FUNCTION')->where('status', 'ACTIVE')->where('is_selectable', true)
            ->where('is_official', true)->where('is_editable', false)->firstOrFail();
        $scopeOption = AssessmentTaxonomyOption::query()
            ->where('tenant_id', $taxonomyOwnerId)->whereKey($this->selectedAssessmentScopeOptionId)
            ->where('dimension', 'SCOPE')->where('status', 'ACTIVE')->where('is_selectable', true)
            ->where('is_official', true)->where('is_editable', false)->firstOrFail();
        $instrumentOption = AssessmentTaxonomyOption::query()
            ->where('tenant_id', $taxonomyOwnerId)->whereKey($this->selectedInstrumentTypeOptionId)
            ->where('dimension', 'INSTRUMENT')->where('status', 'ACTIVE')->where('is_selectable', true)
            ->where('is_official', true)->where('is_editable', false)->firstOrFail();
        $deliveryOption = AssessmentTaxonomyOption::query()
            ->where('tenant_id', $taxonomyOwnerId)->whereKey($this->selectedDeliveryModeOptionId)
            ->where('dimension', 'DELIVERY_MODE')->where('status', 'ACTIVE')->where('is_selectable', true)
            ->where('is_official', true)->where('is_editable', false)->firstOrFail();

        if ($instrumentOption->code !== 'TES_TERTULIS' || $deliveryOption->code !== 'CBT') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selectedDeliveryModeOptionId' => 'Pembuat ujian CBT hanya menerima instrumen tes tertulis dengan mode CBT. Praktik, proyek, portofolio, dan observasi dicatat sebagai asesmen non-CBT.',
            ]);
        }

        $selectedVersionIds = array_values(array_unique($this->selectedQuestionVersionIds));
        $validQuestionCount = QuestionVersion::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $selectedVersionIds)
            ->where('status', 'PUBLISHED')
            ->whereHas('question', fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->where('subject_id', $assignment->subject_id)
                ->where('status', 'PUBLISHED'))
            ->whereHas('curriculumMappings', fn ($query) => $query
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->whereIn('multi_learning_outcome_id', $this->selectedCpIds))
            ->whereHas('tenantOutcomeMappings', fn ($query) => $query
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->whereIn('tenant_learning_outcome_id', $this->selectedOutcomeIds))
            ->count();

        if ($validQuestionCount !== count($selectedVersionIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selectedQuestionVersionIds' => 'Semua soal harus sudah terbit dan memiliki pemetaan CP serta TP/ATP yang sesuai dengan asesmen dan penugasan ini.',
            ]);
        }

        $exam = null;
        DB::transaction(function () use (
            $user, $tenantId, $assignment, $taxonomyOwnerId, $functionOption, $scopeOption,
            $instrumentOption, $deliveryOption, $selectedVersionIds, $startAt, $endAt, &$exam
        ): void {
            $lockedAssignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $assignment->teacher_profile_id)
                ->whereKey($assignment->id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->firstOrFail();

            $assessmentAttributes = [
                'tenant_id' => $tenantId,
                'teacher_subject_assignment_id' => $lockedAssignment->id,
                'subject_id' => $lockedAssignment->subject_id,
                'academic_year_id' => $lockedAssignment->academic_year_id,
                'semester_id' => $lockedAssignment->semester_id,
                'assessment_type' => $functionOption->code,
                'title' => trim($this->title),
                'activity_name' => trim($this->activityName),
                'description' => trim($this->assessmentPurpose),
                'weight' => 1.0,
                'status' => 'ACTIVE',
                'created_by' => $user->id,
                'assessment_function_option_id' => $functionOption->id,
                'assessment_scope_option_id' => $scopeOption->id,
                'instrument_type_option_id' => $instrumentOption->id,
                'delivery_mode_option_id' => $deliveryOption->id,
                'assessment_taxonomy_owner_id' => $taxonomyOwnerId,
                'assessment_origin' => $this->assessmentOrigin,
                'external_program_name' => $this->assessmentOrigin === 'EXTERNAL' ? trim($this->externalProgramName) : null,
                'tenant_curriculum_registration_id' => $lockedAssignment->tenant_curriculum_registration_id,
                'curriculum_version_id' => $lockedAssignment->multi_curriculum_version_id,
                'curriculum_owner_id' => $lockedAssignment->curriculum_owner_id,
                'curriculum_context_snapshot' => [
                    ...($lockedAssignment->curriculum_context_snapshot ?? []),
                    'assessment' => [
                        'activity_name' => trim($this->activityName),
                        'purpose' => trim($this->assessmentPurpose),
                        'origin' => $this->assessmentOrigin,
                        'external_program_name' => $this->assessmentOrigin === 'EXTERNAL' ? trim($this->externalProgramName) : null,
                        'function_option_id' => $functionOption->id,
                        'scope_option_id' => $scopeOption->id,
                        'instrument_option_id' => $instrumentOption->id,
                        'delivery_option_id' => $deliveryOption->id,
                    ],
                ],
            ];

            if ($this->editingExamId) {
                $exam = Exam::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($this->editingExamId)
                    ->with('assessment')
                    ->lockForUpdate()
                    ->firstOrFail();
                Gate::authorize('update', $exam);
                abort_unless($exam->canBeEdited(), 403, 'Ujian yang telah diterbitkan tidak dapat diubah.');
                abort_unless($exam->assessment?->teacher_subject_assignment_id === $lockedAssignment->id, 409, 'Penugasan ujian tidak dapat diganti setelah dibuat.');

                $assessment = $exam->assessment;
                $assessment->update($assessmentAttributes);
                $exam->update([
                    'title' => trim($this->title),
                    'instructions' => trim($this->instructions),
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'duration_seconds' => $this->durationMinutes * 60,
                    'attempt_limit' => $this->attemptLimit,
                    'shuffle_questions' => $this->shuffleQuestions,
                    'shuffle_options' => $this->shuffleOptions,
                ]);
            } else {
                Gate::authorize('create', Assessment::class);
                Gate::authorize('create', Exam::class);
                $assessment = Assessment::query()->create($assessmentAttributes);
                $exam = Exam::query()->create([
                    'tenant_id' => $tenantId,
                    'assessment_id' => $assessment->id,
                    'title' => trim($this->title),
                    'instructions' => trim($this->instructions),
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'duration_seconds' => $this->durationMinutes * 60,
                    'attempt_limit' => $this->attemptLimit,
                    'require_token' => true,
                    'shuffle_questions' => $this->shuffleQuestions,
                    'shuffle_options' => $this->shuffleOptions,
                    'status' => 'DRAFT',
                    'token' => null,
                    'published_at' => null,
                    'locked_at' => null,
                    'created_by' => $user->id,
                ]);
            }

            app(AssessmentCurriculumMappingService::class)->sync(
                $assessment,
                $lockedAssignment,
                $this->selectedCpIds,
                $this->selectedOutcomeIds,
                $this->selectedMaterialId ?: null,
                $this->selectedMaterialTopicId ?: null
            );

            app(ExamSnapshotService::class)->createSnapshots($exam, $selectedVersionIds, false);

            $studentUserIds = DB::table('rombel_student')
                ->join('student_profiles', function ($join) use ($tenantId): void {
                    $join->on('rombel_student.student_id', '=', 'student_profiles.id')
                        ->where('student_profiles.tenant_id', '=', $tenantId)
                        ->whereNull('student_profiles.deleted_at');
                })
                ->join('users', function ($join) use ($tenantId): void {
                    $join->on('student_profiles.user_id', '=', 'users.id')
                        ->where('users.tenant_id', '=', $tenantId);
                })
                ->where('rombel_student.tenant_id', $tenantId)
                ->where('rombel_student.rombel_id', $lockedAssignment->rombel_id)
                ->whereExists(function ($query) use ($tenantId): void {
                    $query->selectRaw('1')
                        ->from('role_user')
                        ->join('roles', 'role_user.role_id', '=', 'roles.id')
                        ->whereColumn('role_user.user_id', 'users.id')
                        ->where('users.tenant_id', $tenantId)
                        ->where('roles.name', 'SISWA');
                })
                ->whereNotExists(function ($query) use ($tenantId): void {
                    $query->selectRaw('1')
                        ->from('role_user')
                        ->join('roles', 'role_user.role_id', '=', 'roles.id')
                        ->whereColumn('role_user.user_id', 'users.id')
                        ->where('users.tenant_id', $tenantId)
                        ->where('roles.name', '!=', 'SISWA');
                })
                ->distinct()
                ->pluck('users.id');

            foreach ($studentUserIds as $studentUserId) {
                ExamParticipant::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'exam_id' => $exam->id,
                    'user_id' => $studentUserId,
                ], [
                    'status' => 'ELIGIBLE',
                    'assigned_at' => now(),
                    'assigned_by' => $user->id,
                ]);
            }

            app(AuditService::class)->log(
                $this->editingExamId ? 'EXAM_UPDATED' : 'EXAM_CREATED',
                'Exam',
                $exam->id,
                null,
                [
                    'assessment_id' => $assessment->id,
                    'teacher_subject_assignment_id' => $lockedAssignment->id,
                    'subject_id' => $lockedAssignment->subject_id,
                    'rombel_id' => $lockedAssignment->rombel_id,
                    'question_version_ids' => $selectedVersionIds,
                    'participant_count' => $studentUserIds->count(),
                    'assessment_function' => $functionOption->code,
                    'assessment_scope' => $scopeOption->code,
                    'instrument_type' => $instrumentOption->code,
                    'delivery_mode' => $deliveryOption->code,
                ]
            );
        }, 3);

        $this->showModal = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Asesmen dan ujian berhasil disimpan sebagai draft. Token dibuat otomatis setelah Guru menerbitkan ujian.']);
    }

    public function generateToken($examId)
    {
        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::where('tenant_id', $tenantId)->where('id', $examId)->firstOrFail();
        Gate::authorize('generateToken', $exam);
        abort_unless(TeacherAccess::canManageExam(auth()->user(), $exam), 403, 'Ujian berada di luar penugasan Guru ini.');
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at) {
            abort(409, 'Token ujian baru tersedia setelah Guru menerbitkan dan mengunci ujian.');
        }
        $newToken = DB::transaction(function () use ($exam) {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('generateToken', $lockedExam);
            abort_unless(TeacherAccess::canManageExam(auth()->user(), $lockedExam), 403, 'Ujian berada di luar penugasan Guru ini.');
            if ($lockedExam->status !== 'PUBLISHED' || !$lockedExam->locked_at) {
                abort(409, 'Token hanya dapat dibuat atau diperbarui setelah ujian diterbitkan.');
            }

            $token = strtoupper(Str::random(6));
            $previousToken = $lockedExam->token;
            $lockedExam->update(['token' => $token, 'require_token' => true]);
            app(\App\Services\AuditService::class)->log(
                'ROTATE_EXAM_TOKEN',
                'Exam',
                $lockedExam->id,
                ['token_present' => !empty($previousToken)],
                ['token_present' => true]
            );

            return $token;
        });

        session()->flash('message', "Token ujian {$exam->title} diperbarui: {$newToken}");
    }

    public function lockExam($examId)
    {
        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::with('snapshots')
            ->where('tenant_id', $tenantId)
            ->where('id', $examId)
            ->firstOrFail();

        Gate::authorize('lock', $exam);
        abort_unless(TeacherAccess::canManageExam(auth()->user(), $exam), 403, 'Ujian berada di luar penugasan Guru ini.');
        abort_unless($exam->assessment_context_snapshot, 409, 'Snapshot konteks asesmen dan kurikulum belum tersedia. Simpan ulang draft sebelum menerbitkan.');

        // Minimal 1 explicit question snapshot must exist before locking
        if ($exam->snapshots->isEmpty()) {
            session()->flash('error', "Gagal mengunci: Ujian '{$exam->title}' belum memiliki soal. Wajib memilih minimal satu soal!");
            return;
        }

        DB::transaction(function () use ($examId, $tenantId) {
            $lockedExam = Exam::with('snapshots')
                ->where('tenant_id', $tenantId)
                ->whereKey($examId)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('lock', $lockedExam);

            if ($lockedExam->status !== 'DRAFT' || $lockedExam->locked_at) {
                abort(409, 'Ujian sudah diterbitkan atau tidak lagi berstatus draf.');
            }
            if ($lockedExam->snapshots->isEmpty()) {
                abort(422, 'Ujian tidak dapat diterbitkan tanpa snapshot soal.');
            }

            $publishedAt = now();
            $tokenGenerated = !$lockedExam->token;
            $lockedExam->update([
                'status' => 'PUBLISHED',
                'locked_at' => $publishedAt,
                'published_at' => $publishedAt,
                'require_token' => true,
                'token' => $tokenGenerated ? strtoupper(Str::random(6)) : $lockedExam->token,
            ]);

            app(\App\Services\AuditService::class)->log(
                'PUBLISH_EXAM',
                'Exam',
                $lockedExam->id,
                ['status' => 'DRAFT'],
                ['status' => 'PUBLISHED', 'token_generated' => $tokenGenerated]
            );
        });

        session()->flash('message', "Ujian {$exam->title} berhasil dikunci, di-snapshot, & diterbitkan.");
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $this->assertCanAccess();
        $tenantId = (string) $user->tenant_id;

        $assignmentRows = app(TeacherPortalDataService::class)->assignmentOptions($user);
        if ($this->selectedAssignmentId === '' && $this->showModal && $assignmentRows !== []) {
            $preferredRow = collect($assignmentRows)->first(
                fn (array $row) => (string) $row['assignment']->subject_id === (string) $this->selectedSubjectId
            ) ?? $assignmentRows[0];
            $this->selectedAssignmentId = (string) $preferredRow['assignment']->id;
            $this->selectedSubjectId = (string) $preferredRow['assignment']->subject_id;
            $this->selectedSemesterId = (string) $preferredRow['assignment']->semester_id;
            $this->selectedRombelId = (string) $preferredRow['assignment']->rombel_id;
        }

        $selectedAssignmentRow = collect($assignmentRows)->first(
            fn (array $row) => (string) $row['assignment']->id === (string) $this->selectedAssignmentId
        );
        $selectedAssignment = $selectedAssignmentRow['assignment'] ?? null;
        $curriculumContext = $selectedAssignmentRow['curriculum_context'] ?? [];

        $taxonomyOptions = [
            'function' => AssessmentTaxonomyOption::query()->where('tenant_id', Tenant::SYSTEM_TENANT_ID)->where('dimension', 'FUNCTION')->where('status', 'ACTIVE')->where('is_selectable', true)->orderBy('sort_order')->get(),
            'scope' => AssessmentTaxonomyOption::query()->where('tenant_id', Tenant::SYSTEM_TENANT_ID)->where('dimension', 'SCOPE')->where('status', 'ACTIVE')->where('is_selectable', true)->orderBy('sort_order')->get(),
            'instrument' => AssessmentTaxonomyOption::query()->where('tenant_id', Tenant::SYSTEM_TENANT_ID)->where('dimension', 'INSTRUMENT')->where('status', 'ACTIVE')->where('is_selectable', true)->orderBy('sort_order')->get(),
            'delivery' => AssessmentTaxonomyOption::query()->where('tenant_id', Tenant::SYSTEM_TENANT_ID)->where('dimension', 'DELIVERY_MODE')->where('status', 'ACTIVE')->where('is_selectable', true)->orderBy('sort_order')->get(),
        ];

        if ($this->showModal) {
            $this->selectedAssessmentFunctionOptionId = $this->selectedAssessmentFunctionOptionId
                ?: (string) ($taxonomyOptions['function']->firstWhere('code', 'SUMATIF')?->id ?? $taxonomyOptions['function']->first()?->id ?? '');
            $this->selectedAssessmentScopeOptionId = $this->selectedAssessmentScopeOptionId
                ?: (string) ($taxonomyOptions['scope']->firstWhere('code', 'LINGKUP_MATERI')?->id ?? $taxonomyOptions['scope']->first()?->id ?? '');
            $this->selectedInstrumentTypeOptionId = $this->selectedInstrumentTypeOptionId
                ?: (string) ($taxonomyOptions['instrument']->firstWhere('code', 'TES_TERTULIS')?->id ?? $taxonomyOptions['instrument']->first()?->id ?? '');
            $this->selectedDeliveryModeOptionId = $this->selectedDeliveryModeOptionId
                ?: (string) ($taxonomyOptions['delivery']->firstWhere('code', 'CBT')?->id ?? $taxonomyOptions['delivery']->first()?->id ?? '');
        }

        $cpOptions = collect($curriculumContext['cp_options'] ?? [])
            ->filter(fn (array $cp) => ($cp['selectable'] ?? false) === true)
            ->values();
        $outcomes = collect($curriculumContext['teacher_outcomes'] ?? [])
            ->filter(fn (array $outcome) => ($outcome['status'] ?? null) === 'PUBLISHED'
                && ($outcome['is_current'] ?? false) === true
                && in_array($outcome['outcome_type'] ?? null, ['TP', 'ATP'], true))
            ->values();

        $materials = $selectedAssignment
            ? Material::query()->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $selectedAssignment->id)
                ->where('status', 'PUBLISHED')->orderBy('material_code')->get()
            : collect();

        $topics = $this->selectedMaterialId !== ''
            ? MaterialTopic::query()->where('tenant_id', $tenantId)
                ->where('material_id', $this->selectedMaterialId)
                ->where('status', 'PUBLISHED')->orderBy('code')->get()
            : collect();

        $query = Exam::query()
            ->with([
                'assessment.subject',
                'assessment.assessmentFunctionOption',
                'assessment.assessmentScopeOption',
                'assessment.instrumentTypeOption',
                'assessment.deliveryModeOption',
                'participants',
                'attempts',
                'snapshots',
            ])
            ->where('tenant_id', $tenantId)
            ->latest();
        TeacherAccess::constrainExamQuery($query, $user);
        $exams = $query->paginate(10);

        $availableQuestions = $selectedAssignment
            ? Question::query()
                ->where('tenant_id', $tenantId)
                ->where('subject_id', $selectedAssignment->subject_id)
                ->where('status', 'PUBLISHED')
                ->whereHas('currentVersion', fn ($versionQuery) => $versionQuery
                    ->where('status', 'PUBLISHED')
                    ->whereHas('curriculumMappings', fn ($mappingQuery) => $mappingQuery
                        ->where('teacher_subject_assignment_id', $selectedAssignment->id)
                        ->when($this->selectedCpIds !== [], fn ($q) => $q->whereIn('multi_learning_outcome_id', $this->selectedCpIds)))
                    ->whereHas('tenantOutcomeMappings', fn ($mappingQuery) => $mappingQuery
                        ->where('teacher_subject_assignment_id', $selectedAssignment->id)
                        ->when($this->selectedOutcomeIds !== [], fn ($q) => $q->whereIn('tenant_learning_outcome_id', $this->selectedOutcomeIds))))
                ->with(['currentVersion.options', 'currentVersion.curriculumMappings.learningOutcome', 'currentVersion.tenantOutcomeMappings.learningOutcome'])
                ->orderBy('question_code')
                ->get()
            : collect();

        return view('livewire.admin.exam-management', [
            'exams' => $exams,
            'assignmentRows' => $assignmentRows,
            'selectedAssignment' => $selectedAssignment,
            'curriculumContext' => $curriculumContext,
            'taxonomyOptions' => $taxonomyOptions,
            'cpOptions' => $cpOptions,
            'outcomes' => $outcomes,
            'materials' => $materials,
            'topics' => $topics,
            'availableQuestions' => $availableQuestions,
        ])->layout('layouts.teacher');
    }
}
