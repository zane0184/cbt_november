<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamSnapshotService;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
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

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'GURU'])->exists()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses manajemen ujian.');
        }
    }

    // Modal State
    public $showModal = false;
    public $editingExamId = null;

    public $title = '';
    public $instructions = 'Kerjakan soal dengan jujur dan teliti.';
    public $selectedSubjectId = '';
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

    public function openCreateModal()
    {
        Gate::authorize('create', Exam::class);
        $this->reset(['editingExamId', 'title', 'selectedSubjectId', 'selectedRombelId', 'confirmAllStudents', 'selectedQuestionVersionIds']);
        $activeYear = AcademicYear::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->first();
        $activeSemester = $activeYear ? Semester::where('tenant_id', auth()->user()->tenant_id)->where('academic_year_id', $activeYear->id)->where('is_active', true)->first() : null;
        $this->selectedSemesterId = $activeSemester?->id ?? '';
        $this->instructions = 'Kerjakan soal dengan jujur dan teliti.';
        $this->durationMinutes = 60;
        $this->attemptLimit = 1;
        $this->requireToken = true;
        $this->shuffleQuestions = true;
        $this->shuffleOptions = true;
        $this->startAt = now()->format('Y-m-d\TH:i');
        $this->endAt = now()->addDays(2)->format('Y-m-d\TH:i');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal($examId)
    {
        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::with(['assessment', 'snapshots'])->where('tenant_id', $tenantId)->findOrFail($examId);
        Gate::authorize('update', $exam);
        $this->editingExamId = $exam->id;
        $this->selectedRombelId = '';
        $this->title = $exam->title;
        $this->instructions = $exam->instructions ?? 'Kerjakan soal dengan jujur dan teliti.';
        $this->selectedSubjectId = $exam->assessment ? $exam->assessment->subject_id : '';
        $this->selectedSemesterId = $exam->assessment ? (string) $exam->assessment->semester_id : '';
        $this->durationMinutes = intval($exam->duration_seconds / 60);
        $this->attemptLimit = $exam->attempt_limit ?? 1;
        $this->requireToken = true;
        $this->shuffleQuestions = $exam->shuffle_questions ?? true;
        $this->shuffleOptions = $exam->shuffle_options ?? true;
        $this->startAt = $exam->start_at ? date('Y-m-d\TH:i', strtotime($exam->start_at)) : now()->format('Y-m-d\TH:i');
        $this->endAt = $exam->end_at ? date('Y-m-d\TH:i', strtotime($exam->end_at)) : now()->addDays(2)->format('Y-m-d\TH:i');

        // Load existing snapshot question versions
        $this->selectedQuestionVersionIds = $exam->snapshots->pluck('source_question_version_id')->toArray();
        $this->confirmAllStudents = false;

        $this->resetValidation();
        $this->showModal = true;
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

    public function saveExam()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'selectedSubjectId' => 'required|exists:subjects,id',
            'selectedSemesterId' => 'required|exists:semesters,id',
            'selectedRombelId' => $this->editingExamId ? 'nullable|string' : 'required|string',
            'durationMinutes' => 'required|integer|min:5|max:360',
            'attemptLimit' => 'required|integer|min:1|max:10',
            'startAt' => 'required|date',
            'endAt' => 'required|date|after:startAt',
        ], [
            'selectedRombelId.required' => 'Pemilihan rombel target peserta wajib dilakukan.',
            'selectedSubjectId.required' => 'Mata pelajaran wajib dipilih.',
        ]);

        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;
        $activeYear = AcademicYear::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $semester = Semester::where('tenant_id', $tenantId)->where('id', $this->selectedSemesterId)->first();
        if (!$activeYear || !$semester || (string) $semester->academic_year_id !== (string) $activeYear->id || !$semester->is_active) {
            $this->addError('selectedSemesterId', 'Pilih semester aktif pada tahun akademik aktif madrasah.');
            return;
        }

        if ($user->hasRole('GURU')) {
            if ($this->editingExamId) {
                $existingExam = Exam::with('assessment')->where('tenant_id', $tenantId)->findOrFail($this->editingExamId);
                Gate::authorize('update', $existingExam);
                if (!$existingExam->assessment
                    || (string) $existingExam->assessment->subject_id !== (string) $this->selectedSubjectId
                    || (string) $existingExam->assessment->semester_id !== (string) $semester->id
                    || (string) $existingExam->assessment->academic_year_id !== (string) $activeYear->id) {
                    abort(403, 'Guru tidak dapat memindahkan ujian yang sudah dibuat ke lingkup akademik lain.');
                }
            }
            if (!TeacherAccess::canCreateAssessment($user, (string) $this->selectedSubjectId, (string) $activeYear->id, (string) $semester->id)) {
                abort(403, 'Guru tidak memiliki penugasan aktif untuk mata pelajaran dan semester ini.');
            }
            if ($this->selectedRombelId === 'ALL') {
                abort(403, 'Guru tidak dapat menugaskan ujian kepada seluruh siswa madrasah.');
            }
            if ($this->selectedRombelId !== null && $this->selectedRombelId !== '') {
                $assignedRombels = TeacherAccess::assignedRombelIdsForScope($user, (string) $this->selectedSubjectId, (string) $activeYear->id, (string) $semester->id);
                if (!in_array((string) $this->selectedRombelId, $assignedRombels, true)) {
                    abort(403, 'Rombel tidak termasuk penugasan aktif Guru untuk mata pelajaran dan semester ini.');
                }
            }
        }

        // 6A: Participant assignment validation
        if ($this->selectedRombelId === 'ALL') {
            if (!$this->confirmAllStudents) {
                $this->addError('confirmAllStudents', 'Anda wajib mengonfirmasi penugasan seluruh siswa madrasah secara eksplisit.');
                return;
            }
        } elseif ($this->selectedRombelId !== null && $this->selectedRombelId !== '') {
            $rombelExists = Rombel::where('tenant_id', $tenantId)->where('id', $this->selectedRombelId)->exists();
            if (!$rombelExists) {
                $this->addError('selectedRombelId', 'Rombel yang dipilih tidak valid atau tidak terdaftar pada tenant ini.');
                return;
            }
        } elseif (!$this->editingExamId) {
            $this->addError('selectedRombelId', 'Pemilihan rombel target peserta wajib dilakukan.');
            return;
        }

        // 6B: Question selection validation
        if (empty($this->selectedQuestionVersionIds)) {
            $hasExistingSnapshots = false;
            if ($this->editingExamId) {
                $existingExam = Exam::where('tenant_id', $tenantId)->find($this->editingExamId);
                if ($existingExam && $existingExam->snapshots()->count() > 0) {
                    $hasExistingSnapshots = true;
                }
            }
            if (!$hasExistingSnapshots) {
                $this->addError('selectedQuestionVersionIds', 'Minimal satu soal harus dipilih secara eksplisit dari Bank Soal.');
                return;
            }
        }

        $selectedVersionIds = array_values(array_unique(array_map('strval', $this->selectedQuestionVersionIds)));
        $validSelectedVersions = QuestionVersion::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $selectedVersionIds)
            ->whereHas('question', fn ($query) => $query->where('tenant_id', $tenantId)->where('subject_id', $this->selectedSubjectId))
            ->count();
        if ($validSelectedVersions !== count($selectedVersionIds)) {
            abort(403, 'Soal yang dipilih tidak seluruhnya berasal dari mata pelajaran dan tenant yang diizinkan.');
        }

        $editingExamId = $this->editingExamId;
        DB::transaction(function () use ($tenantId, $activeYear, $semester, $user, $editingExamId) {
            $oldExamValues = null;
            $exam = null;

            if ($editingExamId) {
                $exam = Exam::with('assessment')
                    ->where('tenant_id', $tenantId)
                    ->whereKey($editingExamId)
                    ->lockForUpdate()
                    ->firstOrFail();
                Gate::authorize('update', $exam);
                if (!$exam->canBeEdited()) {
                    abort(403, 'Ujian sudah diterbitkan dan tidak dapat diubah.');
                }
                $oldExamValues = $exam->only([
                    'assessment_id', 'title', 'start_at', 'end_at',
                    'duration_seconds', 'attempt_limit', 'status', 'require_token',
                ]);
                $exam->update([
                    'title' => $this->title,
                    'instructions' => $this->instructions,
                    'duration_seconds' => $this->durationMinutes * 60,
                    'start_at' => $this->startAt,
                    'end_at' => $this->endAt,
                    'attempt_limit' => $this->attemptLimit,
                    'shuffle_questions' => $this->shuffleQuestions,
                    'shuffle_options' => $this->shuffleOptions,
                    'require_token' => true,
                ]);

                if ($exam->assessment) {
                    $exam->assessment->update([
                        'title' => $this->title,
                        'subject_id' => $this->selectedSubjectId,
                        'academic_year_id' => $activeYear->id,
                        'semester_id' => $semester->id,
                    ]);
                }
            } else {
                Gate::authorize('create', Assessment::class);
                $assessment = Assessment::create([
                    'tenant_id' => $tenantId,
                    'title' => $this->title,
                    'subject_id' => $this->selectedSubjectId,
                    'academic_year_id' => $activeYear->id,
                    'semester_id' => $semester->id,
                    'assessment_type' => 'SUMMATIVE',
                    'status' => 'ACTIVE',
                    'weight' => 1.0,
                    'created_by' => auth()->id(),
                ]);

                $exam = Exam::create([
                    'tenant_id' => $tenantId,
                    'assessment_id' => $assessment->id,
                    'title' => $this->title,
                    'instructions' => $this->instructions,
                    'status' => 'DRAFT',
                    'duration_seconds' => $this->durationMinutes * 60,
                    'start_at' => $this->startAt,
                    'end_at' => $this->endAt,
                    'attempt_limit' => $this->attemptLimit,
                    'shuffle_questions' => $this->shuffleQuestions,
                    'shuffle_options' => $this->shuffleOptions,
                    'require_token' => $this->requireToken,
                    'token' => null,
                    'created_by' => auth()->id(),
                ]);
                $this->editingExamId = $exam->id;
            }

            // Sync Snapshots if questions were selected
            if (!empty($this->selectedQuestionVersionIds)) {
                Gate::authorize('manageQuestions', $exam);
                $snapshotService = app(ExamSnapshotService::class);
                $snapshotService->createSnapshots($exam, $this->selectedQuestionVersionIds, false);
            }

            // Assign Participants (Target Rombel or All Students)
            $targetUserIds = [];
            if ($this->selectedRombelId === 'ALL') {
                if ($user->hasRole('GURU')) abort(403, 'Guru tidak dapat menugaskan ujian kepada seluruh siswa madrasah.');
                $targetUserIds = User::where('tenant_id', $tenantId)
                    ->whereHas('roles', fn($q) => $q->where('name', 'SISWA'))
                    ->pluck('id')->toArray();
            } elseif ($this->selectedRombelId) {
                $studentProfileIds = DB::table('rombel_student')
                    ->where('tenant_id', $tenantId)
                    ->where('rombel_id', $this->selectedRombelId)
                    ->pluck('student_id');

                $targetUserIds = StudentProfile::where('tenant_id', $tenantId)
                    ->whereIn('id', $studentProfileIds)
                    ->pluck('user_id')
                    ->toArray();
            }

            foreach ($targetUserIds as $uid) {
                ExamParticipant::firstOrCreate([
                    'exam_id' => $exam->id,
                    'user_id' => $uid,
                ], [
                    'tenant_id' => $tenantId,
                    'status' => 'ASSIGNED',
                ]);
            }

            // Keep the audit record in the same transaction as the exam,
            // snapshot, and participant changes.
            $exam->refresh();
            app(AuditService::class)->log(
                $editingExamId ? 'UPDATE_EXAM' : 'CREATE_EXAM',
                Exam::class,
                (string) $exam->id,
                $oldExamValues,
                [
                    'assessment_id' => $exam->assessment_id,
                    'title' => $exam->title,
                    'start_at' => $exam->getAttribute('start_at'),
                    'end_at' => $exam->getAttribute('end_at'),
                    'duration_seconds' => $exam->duration_seconds,
                    'attempt_limit' => $exam->attempt_limit,
                    'status' => $exam->status,
                    'require_token' => $exam->require_token,
                    'snapshot_count' => $exam->snapshots()->count(),
                    'participant_count' => $exam->participants()->count(),
                    'target_rombel_id' => $this->selectedRombelId === 'ALL' ? null : ($this->selectedRombelId ?: null),
                    'target_all_students' => $this->selectedRombelId === 'ALL',
                ]
            );
        });

        $this->showModal = false;
        session()->flash('message', "Ujian {$this->title} berhasil disimpan.");
    }

    public function generateToken($examId)
    {
        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::where('tenant_id', $tenantId)->where('id', $examId)->firstOrFail();
        Gate::authorize('generateToken', $exam);
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at) {
            abort(409, 'Token ujian baru tersedia setelah Guru menerbitkan dan mengunci ujian.');
        }
        $newToken = DB::transaction(function () use ($exam) {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('generateToken', $lockedExam);
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

        // 6B: Minimal 1 explicit question snapshot must exist before locking
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

    public function render()
    {
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;
        $activeYear = AcademicYear::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $activeSemester = $activeYear ? Semester::where('tenant_id', $tenantId)->where('academic_year_id', $activeYear->id)->where('is_active', true)->first() : null;
        $examsQuery = Exam::with(['assessment.subject', 'participants', 'attempts', 'snapshots']);
        if ($user->hasRole('GURU')) {
            TeacherAccess::constrainExamQuery($examsQuery, $user);
        }
        $exams = $examsQuery
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->latest()
            ->paginate(12);

        $subjects = Subject::query()->when($user->hasRole('GURU'), fn ($q) => $q->whereIn('id', TeacherAccess::assignedSubjectIds($user)))->get();
        $rombelsQuery = Rombel::with('gradeLevel')->where('tenant_id', $tenantId);
        if ($user->hasRole('GURU') && $activeYear && $activeSemester) {
            $assignedRombelIds = $this->selectedSubjectId
                ? TeacherAccess::assignedRombelIdsForScope($user, (string) $this->selectedSubjectId, (string) $activeYear->id, (string) $activeSemester->id)
                : [];
            $rombelsQuery->whereIn('id', $assignedRombelIds);
        }
        $rombels = $rombelsQuery->get();

        // Available questions for selection based on selectedSubjectId
        $availableQuestions = [];
        if ($this->selectedSubjectId) {
            $availableQuestions = Question::with('currentVersion')
                ->where('tenant_id', $tenantId)
                ->where('subject_id', $this->selectedSubjectId)
                ->when($user->hasRole('GURU'), fn ($q) => $q->whereIn('subject_id', TeacherAccess::assignedSubjectIds($user)))
                ->latest()
                ->get();
        } else {
            $availableQuestions = Question::with('currentVersion')
                ->where('tenant_id', $tenantId)
                ->when($user->hasRole('GURU'), fn ($q) => $q->whereIn('subject_id', TeacherAccess::assignedSubjectIds($user)))
                ->latest()
                ->take(30)
                ->get();
        }

        return view('livewire.admin.exam-management', [
            'exams' => $exams,
            'subjects' => $subjects,
            'rombels' => $rombels,
            'availableQuestions' => $availableQuestions,
            'activeSemester' => $activeSemester,
        ])->layout('layouts.app');
    }
}
