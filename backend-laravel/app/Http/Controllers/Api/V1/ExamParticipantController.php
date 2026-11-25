<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamParticipantController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Exam $exam)
    {
        $this->authorize('monitor', $exam);
        return response()->json(['success' => true, 'data' => $exam->participants()->with('user')->paginate()]);
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

        abort_unless($student->hasRole('SISWA'), 422, 'Peserta ujian harus merupakan akun Siswa.');

        if ($request->user()->hasRole('GURU')) {
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

        $participant = ExamParticipant::updateOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $student->id],
            ['tenant_id' => $exam->tenant_id, 'status' => 'ASSIGNED']
        );

        $this->auditService->log('ADD_PARTICIPANT', 'Exam', $exam->id, null, ['user_id' => $validated['user_id']]);

        return response()->json(['success' => true, 'data' => $participant], 201);
    }

    public function destroy(Exam $exam, ExamParticipant $participant)
    {
        $this->authorize('manageParticipants', $exam);

        if ($participant->exam_id !== $exam->id || $participant->tenant_id !== $exam->tenant_id) abort(404);

        if (request()->user()->hasRole('GURU')) {
            $assessment = $exam->assessment;
            abort_unless($assessment && $participant->user && TeacherAccess::canViewStudentInAssessmentScope(
                request()->user(),
                $participant->user,
                $assessment->subject_id,
                $assessment->academic_year_id,
                $assessment->semester_id
            ), 403);
        }

        $participant->delete();
        $this->auditService->log('REMOVE_PARTICIPANT', 'Exam', $exam->id, ['user_id' => $participant->user_id], null);

        return response()->json(['success' => true]);
    }
}
