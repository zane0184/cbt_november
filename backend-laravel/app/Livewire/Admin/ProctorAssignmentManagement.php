<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProctorAssignmentManagement extends Component
{
    use WithPagination;

    public string $examId = '';
    public string $proctorUserId = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $notes = '';
    public string $examFilter = '';

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('ADMIN_MADRASAH') || !$user->hasPermission('exam.proctor.assign')) {
            abort(403, 'Hanya Admin Madrasah dengan permission penugasan Proktor yang dapat mengakses halaman ini.');
        }
    }

    public function updatedExamFilter(): void
    {
        $this->resetPage();
    }

    public function saveAssignment(ExamProctorAssignmentService $assignmentService): void
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $this->validate([
            'examId' => ['required', 'uuid', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
            'proctorUserId' => ['required', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $exam = Exam::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($validated['examId'])
            ->firstOrFail();

        $proctor = User::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($validated['proctorUserId'])
            ->firstOrFail();

        if (!$proctor->hasRole('PROKTOR')) {
            $this->addError('proctorUserId', 'Akun yang dipilih belum memiliki role PROKTOR pada madrasah ini.');
            return;
        }

        $assignmentService->assign($exam, $proctor, [
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->reset(['examId', 'proctorUserId', 'startsAt', 'endsAt', 'notes']);
        $this->resetValidation();
        session()->flash('message', 'Penugasan Proktor berhasil dibuat atau diperbarui. Perubahan tercatat di audit log.');
    }

    public function revokeAssignment(string $assignmentId, ExamProctorAssignmentService $assignmentService): void
    {
        $tenantId = auth()->user()->tenant_id;

        $assignment = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $exam = Exam::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($assignment->exam_id)
            ->firstOrFail();

        $assignmentService->revoke($exam, $assignment);

        session()->flash('message', 'Penugasan Proktor berhasil dicabut dan dicatat di audit log.');
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $exams = Exam::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('start_at')
            ->get(['id', 'title', 'status', 'start_at', 'end_at', 'timezone']);

        $proctors = User::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn ($query) => $query->where('name', 'PROKTOR'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $selectedExamTimezone = $exams->firstWhere('id', $this->examId)?->timezone ?: config('app.timezone', 'UTC');

        $assignments = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->with(['exam:id,title,status,start_at,end_at,timezone', 'proctor:id,name,email', 'assigner:id,name', 'revoker:id,name'])
            ->when($this->examFilter !== '', fn ($query) => $query->where('exam_id', $this->examFilter))
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('livewire.admin.proctor-assignment-management', [
            'exams' => $exams,
            'proctors' => $proctors,
            'assignments' => $assignments,
            'selectedExamTimezone' => $selectedExamTimezone,
        ])->layout('layouts.app');
    }
}
