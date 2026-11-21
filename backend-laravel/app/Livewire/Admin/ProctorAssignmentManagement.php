<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\ExamSession;
use App\Models\ExamSessionRoom;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProctorAssignmentManagement extends Component
{
    public string $selectedSessionRoomId = '';
    public string $selectedProctorId = '';
    public string $startsAt = '';
    public string $expiresAt = '';
    public string $reason = '';
    public string $revokeReason = '';

    public function boot(): void
    {
        $this->assertCanManage();
    }

    public function mount(): void
    {
        $this->assertCanManage();
        $this->startsAt = now()->format('Y-m-d\\TH:i');
    }

    public function updatedSelectedSessionRoomId(string $sessionRoomId): void
    {
        $scope = ExamSessionRoom::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($sessionRoomId)
            ->with('session')
            ->first();

        if ($scope?->session) {
            $start = $scope->session->starts_at;
            $this->startsAt = ($start->isPast() ? now() : $start)->format('Y-m-d\\TH:i');
            $this->expiresAt = $scope->session->ends_at->format('Y-m-d\\TH:i');
        }
    }

    public function assign(ExamProctorAssignmentService $service): void
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedSessionRoomId' => [
                'required',
                'uuid',
                Rule::exists('exam_session_rooms', 'id')->where('tenant_id', $tenantId),
            ],
            'selectedProctorId' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
            'startsAt' => ['required', 'date'],
            'expiresAt' => ['required', 'date', 'after:startsAt'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $scope = ExamSessionRoom::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($data['selectedSessionRoomId'])
            ->with('exam')
            ->firstOrFail();

        $proctor = User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereHas('teacherProfile', fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'))
            ->find($data['selectedProctorId']);

        if (!$proctor) {
            throw ValidationException::withMessages([
                'selectedProctorId' => 'Penugasan hanya dapat diberikan kepada Guru dengan profil aktif pada tenant ini.',
            ]);
        }

        $service->assign(
            auth()->user(),
            $scope->exam,
            $scope->id,
            $proctor->id,
            Carbon::parse($data['startsAt']),
            Carbon::parse($data['expiresAt']),
            $data['reason']
        );

        $this->reset('reason');
        session()->flash('proctorAssignmentMessage', 'Penugasan tersimpan dengan status PENDING. Guru belum memperoleh capability monitoring sampai Admin mengonfirmasi.');
    }

    public function confirm(string $assignmentId, ExamProctorAssignmentService $service): void
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;
        $assignment = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($assignmentId);
        $exam = Exam::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($assignment->exam_id);

        $service->confirm(auth()->user(), $exam, $assignment);
        session()->flash('proctorAssignmentMessage', 'Penugasan dikonfirmasi untuk ruang-sesi dan revisi jadwal yang tercatat.');
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
        session()->flash('proctorAssignmentMessage', 'Penugasan Proktor dicabut dan histori tetap dipertahankan.');
    }

    private function assertCanManage(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->hasRole('ADMIN_MADRASAH')
                && $user->hasPermission('exam.proctor.assign'),
            403,
            'Hanya Admin Madrasah berizin yang dapat mengelola penugasan Proktor.'
        );
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $sessionRooms = ExamSessionRoom::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->whereHas('session', fn ($query) => $query->whereIn('status', ['SCHEDULED', 'ACTIVE'])->where('ends_at', '>', now()))
            ->whereHas('exam', fn ($query) => $query->where('status', 'PUBLISHED')->whereNotNull('locked_at')->where('end_at', '>', now()))
            ->with([
                'exam:id,title,status,start_at,end_at',
                'session:id,exam_id,name,session_number,starts_at,ends_at,schedule_revision',
                'room:id,code,name,capacity',
            ])
            ->orderBy('created_at')
            ->get();

        $proctors = User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereHas('teacherProfile', fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'))
            ->orderBy('name')
            ->get();

        $assignments = ExamProctorAssignment::query()
            ->where('tenant_id', $tenantId)
            ->with(['exam:id,title', 'sessionRoom.session', 'sessionRoom.room', 'proctor:id,name', 'assigner:id,name', 'revoker:id,name'])
            ->latest()
            ->paginate(20);

        return view('livewire.admin.proctor-assignment-management', compact('sessionRooms', 'proctors', 'assignments'))
            ->layout('layouts.app');
    }
}
