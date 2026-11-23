<?php

namespace App\Services\TeacherPortal;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\Semester;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Security\TeacherPortalAccess;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherPortalDataService
{
    public function __construct(
        private readonly TeacherPortalAccess $access,
        private readonly TeacherCurriculumContextService $curriculumContext
    ) {
    }

    public function dashboard(User $user): array
    {
        $profile = $this->access->profile($user);
        $tenantId = (string) $user->tenant_id;
        $assignments = $this->assignments($profile, $tenantId);
        $activeAssignments = $assignments->filter(
            fn (TeacherSubjectAssignment $assignment): bool => $this->assignmentStatus($assignment) === 'Aktif'
        )->values();

        $activeSubjectIds = $activeAssignments->pluck('subject_id')->unique()->values();
        $activeRombelIds = $activeAssignments->pluck('rombel_id')->unique()->values();
        $studentCount = $this->countStudents($tenantId, $activeRombelIds->all());
        $assessmentIds = $this->assessmentIdsForTeacher($user, $tenantId, $activeAssignments);

        return [
            'user' => $user,
            'profile' => $profile,
            'tenant' => Tenant::query()->whereKey($tenantId)->first(),
            'period' => $this->academicContext($tenantId),
            'stats' => [
                'subjects' => $activeSubjectIds->count(),
                'rombels' => $activeRombelIds->count(),
                'students' => $studentCount,
                'assignments' => $activeAssignments->count(),
            ],
            'activity' => [
                'assessments' => $assessmentIds->count(),
                'exams' => $assessmentIds->isEmpty()
                    ? 0
                    : Exam::query()->where('tenant_id', $tenantId)->whereIn('assessment_id', $assessmentIds)->count(),
            ],
            'activeAssignments' => $this->assignmentRows($activeAssignments, $tenantId),
            'curriculum' => $this->curriculumContext->forTenant($tenantId),
        ];
    }

    public function learning(User $user): array
    {
        $profile = $this->access->profile($user);
        $tenantId = (string) $user->tenant_id;
        $assignments = $this->assignments($profile, $tenantId);

        return [
            'user' => $user,
            'profile' => $profile,
            'period' => $this->academicContext($tenantId),
            'assignments' => $this->assignmentRows($assignments, $tenantId),
            'active_assignment_count' => $assignments
                ->filter(fn (TeacherSubjectAssignment $assignment): bool => $this->assignmentStatus($assignment) === 'Aktif')
                ->count(),
            'curriculum' => $this->curriculumContext->forTenant($tenantId),
        ];
    }

    private function assignments(TeacherProfile $profile, string $tenantId): Collection
    {
        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('teacher_profile_id', $profile->id)
            ->whereHas('teacher', fn ($query) => $query->where('tenant_id', $tenantId)->where('user_id', $profile->user_id))
            ->whereHas('subject', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereHas('rombel', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereHas('rombel.gradeLevel.phase', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereHas('academicYear', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereHas('semester', fn ($query) => $query->where('tenant_id', $tenantId))
            ->with([
                'subject',
                'rombel.gradeLevel',
                'academicYear',
                'semester',
            ])
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (TeacherSubjectAssignment $assignment): bool =>
                $assignment->subject
                && $assignment->rombel
                && $assignment->academicYear
                && $assignment->semester
                && (string) $assignment->subject->tenant_id === $tenantId
                && (string) $assignment->rombel->tenant_id === $tenantId
                && (string) $assignment->rombel->gradeLevel?->tenant_id === $tenantId
                && (string) $assignment->rombel->gradeLevel?->phase?->tenant_id === $tenantId
                && (string) $assignment->rombel->academic_year_id === (string) $assignment->academic_year_id
                && (string) $assignment->semester->academic_year_id === (string) $assignment->academic_year_id
                && (string) $assignment->academicYear->tenant_id === $tenantId
                && (string) $assignment->semester->tenant_id === $tenantId
            )
            ->values();
    }

    private function assignmentRows(Collection $assignments, string $tenantId): array
    {
        $rombelIds = $assignments->pluck('rombel_id')->unique()->values()->all();
        $studentCounts = $this->studentCountsByRombel($tenantId, $rombelIds);

        return $assignments->map(function (TeacherSubjectAssignment $assignment) use ($studentCounts): array {
            return [
                'assignment' => $assignment,
                'status_label' => $this->assignmentStatus($assignment),
                'students_count' => (int) ($studentCounts[$assignment->rombel_id] ?? 0),
            ];
        })->all();
    }

    private function assignmentStatus(TeacherSubjectAssignment $assignment): string
    {
        if ($assignment->status !== 'ACTIVE') {
            return 'Nonaktif';
        }

        $today = Carbon::today()->toDateString();
        $startsAt = $assignment->starts_at?->toDateString();
        $endsAt = $assignment->ends_at?->toDateString();

        if ($startsAt && $startsAt > $today) {
            return 'Belum Berlaku';
        }

        if ($endsAt && $endsAt < $today) {
            return 'Masa Berlaku Berakhir';
        }

        return 'Aktif';
    }

    private function studentCountsByRombel(string $tenantId, array $rombelIds): array
    {
        if ($rombelIds === []) {
            return [];
        }

        return DB::table('rombel_student')
            ->join('student_profiles', function ($join) use ($tenantId): void {
                $join->on('student_profiles.id', '=', 'rombel_student.student_id')
                    ->where('student_profiles.tenant_id', '=', $tenantId)
                    ->whereNull('student_profiles.deleted_at');
            })
            ->join('users', function ($join) use ($tenantId): void {
                $join->on('users.id', '=', 'student_profiles.user_id')
                    ->where('users.tenant_id', '=', $tenantId)
                    ->whereNull('users.deleted_at');
            })
            ->join('rombels', function ($join) use ($tenantId): void {
                $join->on('rombels.id', '=', 'rombel_student.rombel_id')
                    ->where('rombels.tenant_id', '=', $tenantId)
                    ->whereNull('rombels.deleted_at')
                    ->where('rombels.status', '=', 'ACTIVE');
            })
            ->where('rombel_student.tenant_id', $tenantId)
            ->whereIn('rombel_student.rombel_id', $rombelIds)
            ->whereIn('student_profiles.user_id', $this->eligibleStudentUserIds($tenantId))
            ->groupBy('rombel_student.rombel_id')
            ->selectRaw('rombel_student.rombel_id, COUNT(DISTINCT student_profiles.id) AS students_count')
            ->pluck('students_count', 'rombel_id')
            ->all();
    }

    private function countStudents(string $tenantId, array $rombelIds): int
    {
        if ($rombelIds === []) {
            return 0;
        }

        return (int) DB::table('rombel_student')
            ->join('student_profiles', function ($join) use ($tenantId): void {
                $join->on('student_profiles.id', '=', 'rombel_student.student_id')
                    ->where('student_profiles.tenant_id', '=', $tenantId)
                    ->whereNull('student_profiles.deleted_at');
            })
            ->join('users', function ($join) use ($tenantId): void {
                $join->on('users.id', '=', 'student_profiles.user_id')
                    ->where('users.tenant_id', '=', $tenantId)
                    ->whereNull('users.deleted_at');
            })
            ->join('rombels', function ($join) use ($tenantId): void {
                $join->on('rombels.id', '=', 'rombel_student.rombel_id')
                    ->where('rombels.tenant_id', '=', $tenantId)
                    ->whereNull('rombels.deleted_at')
                    ->where('rombels.status', '=', 'ACTIVE');
            })
            ->where('rombel_student.tenant_id', $tenantId)
            ->whereIn('rombel_student.rombel_id', $rombelIds)
            ->whereIn('student_profiles.user_id', $this->eligibleStudentUserIds($tenantId))
            ->distinct()
            ->count('student_profiles.id');
    }

    private function assessmentIdsForTeacher(User $user, string $tenantId, Collection $assignments): Collection
    {
        $query = Assessment::query()
            ->where('tenant_id', $tenantId)
            ->where('created_by', $user->id);

        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0')->pluck('id');
        }

        $query->where(function ($scope) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $scope->orWhere(function ($exactScope) use ($assignment): void {
                    $exactScope->where('subject_id', $assignment->subject_id)
                        ->where('academic_year_id', $assignment->academic_year_id)
                        ->where('semester_id', $assignment->semester_id);
                });
            }
        });

        return $query->pluck('id');
    }

    /**
     * Dashboard statistics count only users with exactly one persisted portal role: SISWA.
     * The legacy users.role column is not authoritative; role_user + roles is canonical.
     */
    private function eligibleStudentUserIds(string $tenantId)
    {
        return DB::table('users as eligible_users')
            ->join('role_user as eligible_role_user', 'eligible_role_user.user_id', '=', 'eligible_users.id')
            ->join('roles as eligible_roles', 'eligible_roles.id', '=', 'eligible_role_user.role_id')
            ->where('eligible_users.tenant_id', $tenantId)
            ->whereNull('eligible_users.deleted_at')
            ->groupBy('eligible_users.id')
            ->havingRaw('COUNT(DISTINCT eligible_roles.id) = 1')
            ->havingRaw('MAX(eligible_roles.name) = ?', ['SISWA'])
            ->select('eligible_users.id');
    }

    private function academicContext(string $tenantId): array
    {
        $activeYears = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $activeSemesters = Semester::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('term')
            ->get();

        $year = $activeYears->count() === 1 ? $activeYears->first() : null;
        $semester = $activeSemesters->count() === 1 ? $activeSemesters->first() : null;

        if ($activeYears->count() > 1 || $activeSemesters->count() > 1) {
            $status = 'Perlu Ditinjau Admin';
            $semester = null;
        } elseif (!$year) {
            $status = $activeSemesters->isEmpty() ? 'Belum Ditetapkan' : 'Perlu Ditinjau Admin';
            $semester = null;
        } elseif (!$semester) {
            $status = 'Belum Ditetapkan';
        } elseif ((string) $semester->academic_year_id !== (string) $year->id) {
            $status = 'Perlu Ditinjau Admin';
            $semester = null;
        } else {
            $status = 'Aktif';
        }

        return [
            'academic_year' => $year,
            'semester' => $semester,
            'status' => $status,
            'year_status' => $year ? 'Aktif' : ($activeYears->isEmpty() ? 'Belum Ditetapkan' : 'Perlu Ditinjau Admin'),
        ];
    }
}
