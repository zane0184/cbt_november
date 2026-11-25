<?php
namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ProctorAssignmentManagement extends Component
{
    public string $selectedExamId = '';
    public string $selectedProctorId = '';
    public string $startsAt = '';
    public string $expiresAt = '';
    public string $reason = '';
    public string $revokeReason = '';

    public function mount(): void
    {
        $this->assertCanManage();
        $this->startsAt = now()->format('Y-m-d\\TH:i');
    }

    public function updatedSelectedExamId(string $examId): void
    {
        $exam = Exam::query()->where('tenant_id', auth()->user()->tenant_id)->whereKey($examId)->first();
        if ($exam?->end_at) $this->expiresAt = $exam->end_at->format('Y-m-d\\TH:i');
    }

    public function assign(ExamProctorAssignmentService $service): void
    {
        $this->assertCanManage();
        abort(403, 'Penugasan pengawas dinonaktifkan sampai kelayakan ujian terpusat memiliki penanda kanonis yang ditetapkan dan diaudit.');
        $data = $this->validate([
            'selectedExamId' => ['required', 'uuid', 'exists:exams,id'],
            'selectedProctorId' => ['required', 'uuid', 'exists:users,id'],
            'startsAt' => ['required', 'date'],
            'expiresAt' => ['required', 'date', 'after:startsAt'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::query()->where('tenant_id', $tenantId)->findOrFail($data['selectedExamId']);
        $proctor = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->has('roles', '=', 1)
            ->whereHas('teacherProfile')
            ->findOrFail($data['selectedProctorId']);

        $service->assign(auth()->user(), $exam, $proctor->id, Carbon::parse($data['startsAt']), Carbon::parse($data['expiresAt']), $data['reason']);
        $this->reset('reason');
        session()->flash('proctorAssignmentMessage', 'Penugasan Guru Pengawas berhasil disimpan dan dicatat.');
    }

    public function revoke(string $assignmentId, ExamProctorAssignmentService $service): void
    {
        $this->assertCanManage();
        $data = $this->validate(['revokeReason' => ['required', 'string', 'min:10', 'max:1000']]);
        $tenantId = auth()->user()->tenant_id;
        $assignment = ExamProctorAssignment::query()->where('tenant_id', $tenantId)->findOrFail($assignmentId);
        $exam = Exam::query()->where('tenant_id', $tenantId)->findOrFail($assignment->exam_id);
        $service->revoke(auth()->user(), $exam, $assignment, $data['revokeReason']);
        $this->reset('revokeReason');
        session()->flash('proctorAssignmentMessage', 'Penugasan Proktor berhasil dicabut dan dicatat.');
    }

    private function assertCanManage(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->tenant_id && $user->roles()->count() === 1 && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists() && $user->hasPermission('exam.proctor.assign'),
            403, 'Hanya Admin Madrasah berizin yang dapat mengelola penugasan Proktor.');
    }

    public function render()
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;

        // Central-exam eligibility has no owner-approved canonical marker yet.
        // Do not expose every published Exam or allow assignment creation.
        $exams = collect();
        $proctors = collect();
        $assignments = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->with(['exam:id,title,status,start_at,end_at', 'proctor:id,name,email', 'assigner:id,name', 'revoker:id,name'])
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $assignmentCreationBlocked = true;
        $assignmentBlockReason = 'Penugasan baru ditahan. Kelayakan ujian terpusat belum memiliki penanda kanonis yang ditetapkan owner. Riwayat penugasan tetap ditampilkan.';

        return view('livewire.admin.proctor-assignment-management', compact(
            'exams',
            'proctors',
            'assignments',
            'assignmentCreationBlocked',
            'assignmentBlockReason'
        ))->layout('layouts.app');
    }
}
