<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherSubjectAssignmentController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = TeacherSubjectAssignment::query()
            ->with(['teacher:id,full_name', 'subject:id,name,code', 'rombel:id,name', 'academicYear:id,name', 'semester:id,term'])
            ->orderByDesc('created_at');

        if ($user->hasExactRole('GURU')) {
            $profile = TeacherProfile::query()->where('user_id', $user->id)->first();
            abort_unless($profile, 403, 'Profil guru tidak ditemukan.');
            $query->where('teacher_profile_id', $profile->id);
        } elseif (!$this->canManage($user)) {
            abort(403, 'Tidak memiliki akses penugasan guru.');
        }

        $filters = $request->validate([
            'teacher_profile_id' => ['sometimes', 'uuid'],
            'subject_id' => ['sometimes', 'uuid'],
            'rombel_id' => ['sometimes', 'uuid'],
            'academic_year_id' => ['sometimes', 'uuid'],
            'semester_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(25)]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canManage($request->user()), 403);
        $data = $this->validatedData($request);
        $tenantId = $request->user()->tenant_id;

        try {
            $assignment = DB::transaction(function () use ($data, $request, $tenantId) {
                $this->assertSameTenantAndAcademicContext($data, $tenantId, true);
                $assignment = TeacherSubjectAssignment::create(array_merge($data, [
                    'tenant_id' => $tenantId,
                    'created_by' => $request->user()->id,
                ]));
                $this->auditService->log('TEACHER_ASSIGNMENT_CREATE', 'TeacherSubjectAssignment', $assignment->id, null, $assignment->toArray());
                return $assignment;
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateAssignment($exception)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penugasan dengan kombinasi Guru, mapel, rombel, tahun ajaran, dan semester tersebut sudah ada.',
                    'code' => 'DUPLICATE_TEACHER_ASSIGNMENT',
                ], 409);
            }
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'data' => $assignment->load(['teacher', 'subject', 'rombel', 'academicYear', 'semester']),
        ], 201);
    }

    public function show(Request $request, TeacherSubjectAssignment $teacherSubjectAssignment)
    {
        $this->authorizeRead($request, $teacherSubjectAssignment);
        return response()->json(['success' => true, 'data' => $teacherSubjectAssignment->load(['teacher', 'subject', 'rombel', 'academicYear', 'semester'])]);
    }

    public function update(Request $request, TeacherSubjectAssignment $teacherSubjectAssignment)
    {
        abort_unless($this->canManage($request->user()), 403);
        $data = $this->validatedData($request, true);
        $tenantId = $request->user()->tenant_id;

        try {
            $updated = DB::transaction(function () use ($data, $teacherSubjectAssignment, $tenantId) {
                $assignment = TeacherSubjectAssignment::query()->whereKey($teacherSubjectAssignment->id)->lockForUpdate()->firstOrFail();
                $merged = array_merge($assignment->only([
                    'teacher_profile_id', 'subject_id', 'rombel_id', 'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
                ]), $data);
                $this->assertSameTenantAndAcademicContext($merged, $tenantId, true);
                $oldValues = $assignment->toArray();
                $assignment->update($data);
                $this->auditService->log('TEACHER_ASSIGNMENT_UPDATE', 'TeacherSubjectAssignment', $assignment->id, $oldValues, $assignment->fresh()->toArray());
                return $assignment->fresh()->load(['teacher', 'subject', 'rombel', 'academicYear', 'semester']);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateAssignment($exception)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perubahan menghasilkan kombinasi penugasan yang sudah ada.',
                    'code' => 'DUPLICATE_TEACHER_ASSIGNMENT',
                ], 409);
            }
            throw $exception;
        }

        return response()->json(['success' => true, 'data' => $updated]);
    }

    public function destroy(Request $request, TeacherSubjectAssignment $teacherSubjectAssignment)
    {
        abort_unless($this->canManage($request->user()), 403);
        DB::transaction(function () use ($teacherSubjectAssignment) {
            $assignment = TeacherSubjectAssignment::query()->whereKey($teacherSubjectAssignment->id)->lockForUpdate()->firstOrFail();
            if ($assignment->status !== 'INACTIVE') {
                $oldValues = $assignment->toArray();
                $assignment->update(['status' => 'INACTIVE']);
                $this->auditService->log('TEACHER_ASSIGNMENT_DEACTIVATE', 'TeacherSubjectAssignment', $assignment->id, $oldValues, $assignment->fresh()->toArray());
            }
        });

        return response()->json(['success' => true, 'message' => 'Penugasan dinonaktifkan. Riwayat tetap dipertahankan.']);
    }

    private function isDuplicateAssignment(QueryException $exception): bool
    {
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = $exception->errorInfo[2] ?? $exception->getMessage();

        return (int) $driverCode === 1062
            || str_contains((string) $message, 'teacher_subject_assignment_unique');
    }

    private function canManage($user): bool
    {
        return $user !== null
            && $user->tenant_id !== null
            && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('academic.manage');
    }

    private function authorizeRead(Request $request, TeacherSubjectAssignment $assignment): void
    {
        $user = $request->user();
        if ($this->canManage($user)) {
            return;
        }

        if ($user->hasExactRole('GURU')) {
            $profile = TeacherProfile::query()->where('user_id', $user->id)->first();
            abort_unless($profile && $assignment->teacher_profile_id === $profile->id, 403);
            return;
        }

        abort(403);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'teacher_profile_id' => [$required, 'uuid'],
            'subject_id' => [$required, 'uuid'],
            'rombel_id' => [$required, 'uuid'],
            'academic_year_id' => [$required, 'uuid'],
            'semester_id' => [$required, 'uuid'],
            'starts_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'ends_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        if (array_key_exists('starts_at', $data) && array_key_exists('ends_at', $data)
            && $data['starts_at'] && $data['ends_at'] && $data['ends_at'] < $data['starts_at']) {
            throw ValidationException::withMessages(['ends_at' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.']);
        }

        return $data;
    }

    private function assertSameTenantAndAcademicContext(array $data, string $tenantId, bool $lock = false): void
    {
        $find = static function ($model, $id) use ($lock) {
            $query = $model::query()->whereKey($id);
            if ($lock) {
                $query->lockForUpdate();
            }
            return $query->first();
        };

        $teacher = $find(TeacherProfile::class, $data['teacher_profile_id']);
        $subject = $find(Subject::class, $data['subject_id']);
        $rombel = $find(Rombel::class, $data['rombel_id']);
        $year = $find(AcademicYear::class, $data['academic_year_id']);
        $semester = $find(Semester::class, $data['semester_id']);

        if (!$teacher || !$subject || !$rombel || !$year || !$semester) {
            throw ValidationException::withMessages(['assignment' => 'Referensi tidak ditemukan dalam tenant aktif.']);
        }

        if ($teacher->tenant_id !== $tenantId || $subject->tenant_id !== $tenantId
            || $rombel->tenant_id !== $tenantId || $year->tenant_id !== $tenantId
            || $semester->tenant_id !== $tenantId) {
            throw ValidationException::withMessages(['assignment' => 'Seluruh referensi harus berasal dari tenant aktif yang sama.']);
        }

        if ($rombel->academic_year_id !== $year->id || $semester->academic_year_id !== $year->id) {
            throw ValidationException::withMessages(['academic_year_id' => 'Rombel dan semester harus berada pada tahun ajaran yang sama.']);
        }

        $teacherUser = $teacher->user;
        if (!$teacherUser || $teacherUser->deleted_at !== null || $teacherUser->tenant_id !== $tenantId || !$teacherUser->hasExactRole('GURU')) {
            throw ValidationException::withMessages(['teacher_profile_id' => 'Profil harus terhubung ke akun Guru aktif dalam tenant yang sama.']);
        }

        if (!empty($data['starts_at']) && !empty($data['ends_at'])
            && Carbon::parse($data['ends_at'])->lt(Carbon::parse($data['starts_at']))) {
            throw ValidationException::withMessages(['ends_at' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.']);
        }
    }
}
