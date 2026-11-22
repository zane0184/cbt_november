<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Support\Security\TeacherPortalAccess;
use Livewire\Component;

/**
 * Shared request-boundary authorization for Guru/Kamad result surfaces.
 *
 * booted() runs after mount/hydration, so every Livewire request re-reads the
 * authenticated user's persisted roles and re-authorizes the hydrated models.
 */
abstract class TeacherResultComponent extends Component
{
    public function booted(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->tenant_id, 403);

        $persistedUserExists = User::query()
            ->whereKey($user->getKey())
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('deleted_at')
            ->exists();

        abort_unless($persistedUserExists, 403, 'Akun tidak lagi aktif pada tenant ini.');

        // User::hasRole/hasPermission read the loaded relation; discard it so
        // revocations made after the previous request are observed immediately.
        $user->unsetRelation('roles');
        $roleNames = $user->roles()->pluck('name')->all();

        if (in_array('GURU', $roleNames, true)) {
            // This also enforces exactly one persisted role GURU and a unique
            // tenant-local teacher profile for the Guru portal.
            app(TeacherPortalAccess::class)->profile($user);
        }

        $user->unsetRelation('roles');

        $exam = isset($this->exam) ? $this->exam : null;
        if ($exam instanceof Exam) {
            $this->authorize('viewExamResults', $exam);
        }

        $attempt = isset($this->attempt) ? $this->attempt : null;
        if ($attempt instanceof ExamAttempt) {
            abort_unless(
                $exam instanceof Exam && (string) $attempt->exam_id === (string) $exam->id,
                404
            );
            $this->authorize('view', $attempt);
        }
    }
}
