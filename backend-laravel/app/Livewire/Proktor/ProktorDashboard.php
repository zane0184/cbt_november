<?php

namespace App\Livewire\Proktor;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamProctorAssignment;
use Livewire\Component;

class ProktorDashboard extends Component
{
    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('PROKTOR')) {
            abort(403, 'Workbench ini hanya untuk Proktor yang memiliki penugasan operasional.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;

        // Only this Proktor's non-revoked assignments are visible. Upcoming
        // assignments are listed, but monitoring links are enabled only inside
        // the assignment's active time window.
        $assignments = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('proctor_user_id', $user->id)
            ->notRevoked()
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with(['exam.subject'])
            ->orderBy('starts_at')
            ->get();

        $activeAssignments = $assignments->filter(fn ($assignment) => $assignment->isActiveAt(now()));
        $activeExamIds = $activeAssignments->pluck('exam_id')->all();
        $activeExams = $activeAssignments->pluck('exam')->filter()->values();

        $inProgressAttempts = $activeExamIds === []
            ? 0
            : ExamAttempt::query()->where('tenant_id', $tenantId)->whereIn('exam_id', $activeExamIds)->where('status', 'IN_PROGRESS')->count();
        $submittedAttempts = $activeExamIds === []
            ? 0
            : ExamAttempt::query()->where('tenant_id', $tenantId)->whereIn('exam_id', $activeExamIds)->where('status', 'SUBMITTED')->count();

        return view('livewire.proktor.proktor-dashboard', [
            'assignments' => $assignments,
            'activeExams' => $activeExams,
            'inProgressAttempts' => $inProgressAttempts,
            'submittedAttempts' => $submittedAttempts,
        ])->layout('layouts.app');
    }
}
