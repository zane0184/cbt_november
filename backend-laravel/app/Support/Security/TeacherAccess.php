<?php

namespace App\Support\Security;

use App\Models\Exam;
use App\Models\Rombel;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Assignment-based authorization for teacher access to academic data. */
class TeacherAccess
{
    public static function profileIds(User $user): array
    {
        return TeacherProfile::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();
    }

    public static function assignedSubjectIds(User $user): array
    {
        $profileIds = self::profileIds($user);
        if ($profileIds === []) {
            return [];
        }

        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('teacher_profile_id', $profileIds)
            ->where('status', 'ACTIVE')
            ->activeForDate()
            ->distinct()
            ->pluck('subject_id')
            ->all();
    }

    public static function canManageSubject(User $user, string $subjectId): bool
    {
        return in_array($subjectId, self::assignedSubjectIds($user), true);
    }

    /** Whether the teacher has an active assignment for this assessment scope. */
    public static function canManageAssessment(User $user, $assessment): bool
    {
        if ($user->tenant_id !== $assessment->tenant_id
            || !$assessment->subject_id
            || !$assessment->academic_year_id
            || !$assessment->semester_id) {
            return false;
        }

        $profileIds = self::profileIds($user);
        if ($profileIds === []) {
            return false;
        }

        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('teacher_profile_id', $profileIds)
            ->where('subject_id', $assessment->subject_id)
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('semester_id', $assessment->semester_id)
            ->activeForDate()
            ->exists();
    }

    public static function canCreateAssessment(User $user, string $subjectId, string $academicYearId, string $semesterId): bool
    {
        $profileIds = self::profileIds($user);
        if ($profileIds === []) {
            return false;
        }

        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('teacher_profile_id', $profileIds)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester_id', $semesterId)
            ->activeForDate()
            ->exists();
    }

    /** Return active rombel IDs for one exact teacher academic scope. */
    public static function assignedRombelIdsForScope(
        User $teacher,
        string $subjectId,
        string $academicYearId,
        string $semesterId
    ): array {
        $profileIds = self::profileIds($teacher);
        if ($profileIds === []) {
            return [];
        }

        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $teacher->tenant_id)
            ->whereIn('teacher_profile_id', $profileIds)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester_id', $semesterId)
            ->activeForDate()
            ->distinct()
            ->pluck('rombel_id')
            ->all();
    }

    /**
     * Verify that one exact teacher assignment covers the assessment AND the
     * target rombel. Separate subject and rombel assignments must never be
     * combined to manufacture authority for a scope that was not assigned.
     */
    public static function canAssignRombelForAssessment(User $user, Rombel $rombel, $assessment): bool
    {
        if ($user->tenant_id !== $rombel->tenant_id
            || $user->tenant_id !== $assessment->tenant_id
            || !$assessment->subject_id
            || !$assessment->academic_year_id
            || !$assessment->semester_id
            || $rombel->academic_year_id !== $assessment->academic_year_id) {
            return false;
        }

        return in_array(
            $rombel->id,
            self::assignedRombelIdsForScope(
                $user,
                $assessment->subject_id,
                $assessment->academic_year_id,
                $assessment->semester_id
            ),
            true
        );
    }

    /**
     * Apply teacher visibility as correlated SQL predicates before pagination.
     * This avoids loading every exam and participant in a tenant into PHP.
     */
    public static function constrainExamQuery(Builder $query, User $user): Builder
    {
        $profileIds = self::profileIds($user);
        if ($profileIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $today = now()->toDateString();

        $query->whereExists(function ($assessmentQuery) use ($profileIds, $today) {
            $assessmentQuery->selectRaw('1')
                ->from('assessments as teacher_assessments')
                ->join('teacher_subject_assignments as teacher_assignments', function ($join) {
                    $join->on('teacher_assignments.tenant_id', '=', 'teacher_assessments.tenant_id')
                        ->on('teacher_assignments.subject_id', '=', 'teacher_assessments.subject_id')
                        ->on('teacher_assignments.academic_year_id', '=', 'teacher_assessments.academic_year_id')
                        ->on('teacher_assignments.semester_id', '=', 'teacher_assessments.semester_id');
                })
                ->whereColumn('teacher_assessments.id', 'exams.assessment_id')
                ->whereColumn('teacher_assessments.tenant_id', 'exams.tenant_id')
                ->whereNull('teacher_assessments.deleted_at')
                ->whereNotNull('teacher_assessments.subject_id')
                ->whereNotNull('teacher_assessments.academic_year_id')
                ->whereNotNull('teacher_assessments.semester_id')
                ->whereIn('teacher_assignments.teacher_profile_id', $profileIds)
                ->where('teacher_assignments.status', 'ACTIVE')
                ->where(function ($dates) use ($today) {
                    $dates->whereNull('teacher_assignments.starts_at')
                        ->orWhereDate('teacher_assignments.starts_at', '<=', $today);
                })
                ->where(function ($dates) use ($today) {
                    $dates->whereNull('teacher_assignments.ends_at')
                        ->orWhereDate('teacher_assignments.ends_at', '>=', $today);
                });
        });

        // An empty participant list is not evidence of teacher coverage.
        // A teacher may see their own unassigned draft so they can continue
        // building it. Any exam with participants must still have every
        // participant covered by the teacher's exact active assignment scope.
        $query->where(function ($visibility) use ($user) {
            $visibility->whereExists(function ($participants) {
                $participants->selectRaw('1')
                    ->from('exam_participants as teacher_present_participants')
                    ->whereColumn('teacher_present_participants.exam_id', 'exams.id')
                    ->whereColumn('teacher_present_participants.tenant_id', 'exams.tenant_id');
            })->orWhere(function ($ownDraft) use ($user) {
                $ownDraft->where('exams.created_by', $user->id)
                    ->whereNull('exams.locked_at')
                    ->where('exams.status', 'DRAFT');
            });
        });

        $query->whereNotExists(function ($uncovered) use ($profileIds, $today) {
            $uncovered->selectRaw('1')
                ->from('exam_participants as teacher_uncovered_participants')
                ->whereColumn('teacher_uncovered_participants.exam_id', 'exams.id')
                ->whereColumn('teacher_uncovered_participants.tenant_id', 'exams.tenant_id')
                ->whereNotExists(function ($coverage) use ($profileIds, $today) {
                    $coverage->selectRaw('1')
                        ->from('student_profiles as teacher_student_profiles')
                        ->join('users as teacher_student_users', function ($join) {
                            $join->on('teacher_student_users.id', '=', 'teacher_student_profiles.user_id')
                                ->on('teacher_student_users.tenant_id', '=', 'teacher_student_profiles.tenant_id');
                        })
                        ->join('rombel_student as teacher_rombel_students', function ($join) {
                            $join->on('teacher_rombel_students.student_id', '=', 'teacher_student_profiles.id')
                                ->on('teacher_rombel_students.tenant_id', '=', 'teacher_student_profiles.tenant_id');
                        })
                        ->join('rombels as teacher_rombels', function ($join) {
                            $join->on('teacher_rombels.id', '=', 'teacher_rombel_students.rombel_id')
                                ->on('teacher_rombels.tenant_id', '=', 'teacher_student_profiles.tenant_id');
                        })
                        ->join('assessments as teacher_coverage_assessments', 'teacher_coverage_assessments.id', '=', 'exams.assessment_id')
                        ->join('teacher_subject_assignments as teacher_coverage_assignments', function ($join) {
                            $join->on('teacher_coverage_assignments.tenant_id', '=', 'teacher_coverage_assessments.tenant_id')
                                ->on('teacher_coverage_assignments.rombel_id', '=', 'teacher_rombels.id')
                                ->on('teacher_coverage_assignments.subject_id', '=', 'teacher_coverage_assessments.subject_id')
                                ->on('teacher_coverage_assignments.academic_year_id', '=', 'teacher_coverage_assessments.academic_year_id')
                                ->on('teacher_coverage_assignments.semester_id', '=', 'teacher_coverage_assessments.semester_id');
                        })
                        ->whereColumn('teacher_student_profiles.user_id', 'teacher_uncovered_participants.user_id')
                        ->whereColumn('teacher_student_users.tenant_id', 'teacher_uncovered_participants.tenant_id')
                        ->whereColumn('teacher_student_profiles.tenant_id', 'teacher_uncovered_participants.tenant_id')
                        ->whereNull('teacher_student_profiles.deleted_at')
                        ->whereNull('teacher_rombels.deleted_at')
                        ->whereNull('teacher_coverage_assessments.deleted_at')
                        ->whereColumn('teacher_coverage_assessments.tenant_id', 'exams.tenant_id')
                        ->whereColumn('teacher_rombels.academic_year_id', 'teacher_coverage_assessments.academic_year_id')
                        ->whereIn('teacher_coverage_assignments.teacher_profile_id', $profileIds)
                        ->where('teacher_coverage_assignments.status', 'ACTIVE')
                        ->where(function ($dates) use ($today) {
                            $dates->whereNull('teacher_coverage_assignments.starts_at')
                                ->orWhereDate('teacher_coverage_assignments.starts_at', '<=', $today);
                        })
                        ->where(function ($dates) use ($today) {
                            $dates->whereNull('teacher_coverage_assignments.ends_at')
                                ->orWhereDate('teacher_coverage_assignments.ends_at', '>=', $today);
                        });
                });
        });

        return $query;
    }

    /**
     * Resolve only exams whose subject/year/semester match the requested scope
     * and whose entire participant roster is covered by this teacher's active
     * rombel assignments. Used by student-level report/analytics queries.
     */
    public static function allowedExamIdsForStudentScope(
        User $teacher,
        string $subjectId,
        string $academicYearId,
        string $semesterId
    ): array {
        $query = Exam::query()->whereHas('assessment', function ($assessmentQuery) use ($subjectId, $academicYearId, $semesterId) {
            $assessmentQuery->where('subject_id', $subjectId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId);
        });

        return self::constrainExamQuery($query, $teacher)
            ->whereExists(function ($participants) {
                $participants->selectRaw('1')
                    ->from('exam_participants as teacher_report_participants')
                    ->whereColumn('teacher_report_participants.exam_id', 'exams.id')
                    ->whereColumn('teacher_report_participants.tenant_id', 'exams.tenant_id');
            })
            ->pluck('exams.id')->all();
    }

    /**
     * An exam is visible only if its subject/year/semester assignment covers
     * every participant through at least one of the student's current rombels.
     */
    /**
     * Management scope for an exam: an empty draft is accessible only to its
     * creator; once participants exist, every participant must be covered by
     * the teacher's exact active assignment scope.
     */
    public static function canManageExam(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id
            || !$exam->assessment
            || !self::canManageAssessment($user, $exam->assessment)) {
            return false;
        }

        $hasParticipants = $exam->participants()
            ->where('exam_participants.tenant_id', $exam->tenant_id)
            ->exists();

        return $hasParticipants
            ? self::canViewExam($user, $exam)
            : $exam->created_by === $user->id;
    }

    public static function canViewExam(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        $assessment = $exam->assessment;
        if (!$assessment
            || $assessment->tenant_id !== $user->tenant_id
            || !$assessment->subject_id
            || !$assessment->academic_year_id
            || !$assessment->semester_id) {
            return false;
        }

        $profileIds = self::profileIds($user);
        if ($profileIds === []) {
            return false;
        }

        $assignedRombelIds = TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('teacher_profile_id', $profileIds)
            ->where('subject_id', $assessment->subject_id)
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('semester_id', $assessment->semester_id)
            ->where('status', 'ACTIVE')
            ->activeForDate()
            ->pluck('rombel_id');

        if ($assignedRombelIds->isEmpty()) {
            return false;
        }

        $participants = $exam->participants()->where('exam_participants.tenant_id', $exam->tenant_id);
        $total = (clone $participants)->count();
        if ($total === 0) {
            return false;
        }

        $covered = (clone $participants)
            ->whereHas('user', function ($userQuery) use ($user, $assignedRombelIds, $assessment) {
                $userQuery->where('users.tenant_id', $user->tenant_id)
                    ->whereHas('studentProfile', function ($profileQuery) use ($user, $assignedRombelIds, $assessment) {
                        $profileQuery->where('student_profiles.tenant_id', $user->tenant_id)
                            ->whereHas('rombels', function ($rombelQuery) use ($user, $assignedRombelIds, $assessment) {
                                $rombelQuery->whereIn('rombels.id', $assignedRombelIds)
                                    ->where('rombels.tenant_id', $user->tenant_id)
                                    ->where('rombels.academic_year_id', $assessment->academic_year_id);
                            });
                    });
            })
            ->count();

        return $total === $covered;
    }

    /**
     * Student result access is subject/rombel/year/semester-specific. A teacher
     * must not gain access to every subject result merely because they teach
     * one subject in the student's rombel.
     */
    public static function canViewStudentInAssessmentScope(
        User $teacher,
        User $student,
        string $subjectId,
        string $academicYearId,
        string $semesterId
    ): bool {
        if ($teacher->tenant_id !== $student->tenant_id || !$student->hasExactRole('SISWA')) {
            return false;
        }

        $profile = $student->studentProfile()
            ->where('tenant_id', $teacher->tenant_id)
            ->with(['rombels' => fn ($query) => $query->where('rombels.tenant_id', $teacher->tenant_id)])
            ->first();

        if (!$profile || $profile->rombels->isEmpty()) {
            return false;
        }

        $assignedRombelIds = self::assignedRombelIdsForScope($teacher, $subjectId, $academicYearId, $semesterId);
        if ($assignedRombelIds === []) {
            return false;
        }

        return $profile->rombels->contains(
            fn (Rombel $rombel) => in_array($rombel->id, $assignedRombelIds, true)
                && $rombel->tenant_id === $teacher->tenant_id
                && $rombel->academic_year_id === $academicYearId
        );
    }


}
