<?php
namespace App\Livewire\Proktor;

use App\Models\ExamAttempt;
use App\Models\ExamProctorAssignment;
use Livewire\Component;

class ProktorDashboard extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->hasRole('GURU') && $user->teacherProfile()->exists()
                && ExamProctorAssignment::query()->activeNow()
                    ->where('tenant_id', $user->tenant_id)
                    ->where('proctor_user_id', $user->id)
                    ->exists(),
            403,
            'Akses pengawasan hanya tersedia bagi Guru dengan penugasan aktif.'
        );
    }

    public function render()
    {
        $user = auth()->user();
        $assignments = ExamProctorAssignment::query()->activeNow()
            ->where('tenant_id', $user->tenant_id)
            ->where('proctor_user_id', $user->id)
            ->with(['exam:id,title,status,start_at,end_at'])->orderBy('expires_at')->get()
            ->filter(fn ($assignment) => $assignment->exam !== null)->values();

        $examIds = $assignments->pluck('exam_id')->all();
        $allExams = $assignments->pluck('exam')->filter()->values();
        $activeExams = $allExams->filter(fn ($exam) => $exam->isOngoing())->values();
        $inProgressAttempts = $examIds === [] ? 0 : ExamAttempt::query()
            ->where('tenant_id', $user->tenant_id)->whereIn('exam_id', $examIds)->where('status', 'IN_PROGRESS')->count();
        $submittedAttempts = $examIds === [] ? 0 : ExamAttempt::query()
            ->where('tenant_id', $user->tenant_id)->whereIn('exam_id', $examIds)->where('status', 'SUBMITTED')->count();

        return view('livewire.proktor.proktor-dashboard', [
            'assignments' => $assignments, 'activeExams' => $activeExams, 'allExams' => $allExams,
            'inProgressAttempts' => $inProgressAttempts, 'submittedAttempts' => $submittedAttempts,
        ])->layout('layouts.app');
    }
}
