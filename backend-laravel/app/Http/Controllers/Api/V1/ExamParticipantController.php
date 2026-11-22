<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Services\AuditService;
use App\Services\ExamMonitoringScope;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamParticipantController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request, Exam $exam, ExamMonitoringScope $scope)
    {
        $this->authorize('monitor', $exam);

        // Temporary Proktor access is room/session scoped. Do not use the
        // unfiltered exam->participants() relation on this operational endpoint.
        $participants = $scope->participantQuery($request->user(), $exam)
            ->whereHas('user', fn ($query) => $query->where('tenant_id', $exam->tenant_id))
            ->with(['user' => fn ($query) => $query
                ->where('tenant_id', $exam->tenant_id)
                ->select(['id', 'name'])])
            ->orderBy('id')
            ->paginate(50);

        $participants->getCollection()->transform(fn (ExamParticipant $participant) => [
            'id' => $participant->id,
            'student_name' => $participant->user?->name,
            'status' => $participant->status,
        ]);

        return response()->json(['success' => true, 'data' => $participants]);
    }

    public function store(Request $request, Exam $exam)
    {
        $this->authorize('manageParticipants', $exam);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $exam->tenant_id)]
        ]);

        $student = \App\Models\User::query()
            ->where('tenant_id', $exam->tenant_id)
            ->findOrFail($validated['user_id']);

        abort_unless($student->hasExactRole('SISWA'), 422, 'Peserta ujian harus merupakan akun Siswa.');

        if ($request->user()->hasExactRole('GURU')) {
            abort_unless(
                $exam->assessment && TeacherAccess::canViewStudentInAssessmentScope(
                    $request->user(),
                    $student,
                    $exam->assessment->subject_id,
                    $exam->assessment->academic_year_id,
                    $exam->assessment->semester_id
                ),
                403,
                'Siswa tidak berada pada rombel yang ditugaskan untuk mapel, tahun ajaran, dan semester asesmen ini.'
            );
        }

        $participant = DB::transaction(function () use ($request, $exam, $student) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize('manageParticipants', $lockedExam);

            if (!$lockedExam->assessment
                || (string) $student->tenant_id !== (string) $lockedExam->tenant_id) {
                abort(422, 'Peserta atau asesmen tidak berada pada tenant ujian.');
            }

            if ($request->user()->hasExactRole('GURU')) {
                abort_unless(TeacherAccess::canViewStudentInAssessmentScope(
                    $request->user(),
                    $student,
                    $lockedExam->assessment->subject_id,
                    $lockedExam->assessment->academic_year_id,
                    $lockedExam->assessment->semester_id
                ), 403, 'Siswa tidak berada pada rombel yang ditugaskan untuk mapel, tahun ajaran, dan semester asesmen ini.');
            }

            // Idempotent add-only: an existing participant may already be STARTED
            // or COMPLETED. Never reset that lifecycle state to ASSIGNED.
            $participant = ExamParticipant::firstOrCreate(
                [
                    'tenant_id' => $lockedExam->tenant_id,
                    'exam_id' => $lockedExam->id,
                    'user_id' => $student->id,
                ],
                ['status' => 'ASSIGNED']
            );

            $this->auditService->log('ADD_PARTICIPANT', 'Exam', $lockedExam->id, null, [
                'user_id' => $student->id,
                'created' => $participant->wasRecentlyCreated,
                'semantics' => 'ADD_ONLY',
            ]);

            return $participant;
        });

        return response()->json(['success' => true, 'data' => $participant], $participant->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Exam $exam, ExamParticipant $participant)
    {
        $this->authorize('manageParticipants', $exam);

        if ($participant->exam_id !== $exam->id || $participant->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        DB::transaction(function () use ($exam, $participant) {
            // Same parent-first order as attempt creation: Exam -> Participant -> Attempt.
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize('manageParticipants', $lockedExam);

            $lockedParticipant = ExamParticipant::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParticipant->attempts()->exists()) {
                abort(409, 'Peserta tidak dapat dihapus setelah memiliki riwayat attempt ujian.');
            }

            if (request()->user()->hasExactRole('GURU')) {
                $assessment = $lockedExam->assessment;
                abort_unless($assessment && $lockedParticipant->user
                    && TeacherAccess::canViewStudentInAssessmentScope(
                        request()->user(),
                        $lockedParticipant->user,
                        $assessment->subject_id,
                        $assessment->academic_year_id,
                        $assessment->semester_id
                    ), 403);
            }

            $userId = $lockedParticipant->user_id;
            $lockedParticipant->delete();
            $this->auditService->log('REMOVE_PARTICIPANT', 'Exam', $lockedExam->id, ['user_id' => $userId], null);
        });

        return response()->json(['success' => true]);
    }
}
