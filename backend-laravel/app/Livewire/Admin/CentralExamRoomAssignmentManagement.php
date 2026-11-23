<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use App\Models\Room;
use App\Models\User;
use App\Services\ExamRoomAssignmentService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class CentralExamRoomAssignmentManagement extends Component
{
    public string $selectedExamId = '';
    public string $selectedRoomId = '';
    public array $selectedProctorIds = [];
    public array $supervisorIds = [];
    public string $sessionNumber = '1';
    public string $scheduledStart = '';
    public string $scheduledEnd = '';
    public bool $mergeRoom = false;
    public string $revokeReason = '';

    public function mount(): void
    {
        $this->assertCanManage();
    }

    public function updatedSelectedExamId(string $examId): void
    {
        $exam = Exam::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('category', Exam::CENTRALIZED_CATEGORIES)
            ->where('is_centralized', true)
            ->whereKey($examId)->first();

        if ($exam) {
            $this->scheduledStart = $exam->start_at?->format('Y-m-d\\TH:i') ?? '';
            $this->scheduledEnd = $exam->end_at?->format('Y-m-d\\TH:i') ?? '';
        }
    }

    public function assign(ExamRoomAssignmentService $service): void
    {
        $this->assertCanManage();

        $data = $this->validate([
            'selectedExamId' => ['required', 'uuid'],
            'selectedRoomId' => ['required', 'uuid'],
            'selectedProctorIds' => ['required', 'array', 'min:1'],
            'selectedProctorIds.*' => ['required', 'uuid', 'distinct'],
            'supervisorIds' => ['array'],
            'supervisorIds.*' => ['required', 'uuid'],
            'sessionNumber' => ['required', 'integer', 'min:1', 'max:65535'],
            'scheduledStart' => ['required', 'date'],
            'scheduledEnd' => ['required', 'date', 'after:scheduledStart'],
            'mergeRoom' => ['boolean'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $exam = Exam::query()->where('tenant_id', $tenantId)
            ->whereIn('category', Exam::CENTRALIZED_CATEGORIES)
            ->where('is_centralized', true)
            ->findOrFail($data['selectedExamId']);
        $room = Room::query()->where('tenant_id', $tenantId)->findOrFail($data['selectedRoomId']);

        $proctors = [];
        foreach ($data['selectedProctorIds'] as $userId) {
            $proctors[$userId] = in_array($userId, $data['supervisorIds'] ?? [], true)
                ? 'supervisor'
                : 'proctor';
        }

        $service->create(
            auth()->user(),
            $exam,
            $room,
            $proctors,
            (int) $data['sessionNumber'],
            Carbon::parse($data['scheduledStart']),
            Carbon::parse($data['scheduledEnd']),
            (bool) $data['mergeRoom']
        );

        $this->reset('selectedProctorIds', 'supervisorIds', 'mergeRoom');
        session()->flash('centralRoomAssignmentMessage', 'Ruang, sesi, dan Guru pengawas berhasil ditetapkan serta dicatat.');
    }

    public function addProctor(string $assignmentId, string $userId, string $roleLabel, ExamRoomAssignmentService $service): void
    {
        $this->assertCanManage();
        $assignment = ExamRoomAssignment::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($assignmentId);

        $service->addProctor(auth()->user(), $assignment, $userId, $roleLabel);
        session()->flash('centralRoomAssignmentMessage', 'Guru pengawas berhasil ditambahkan ke ruang/sesi.');
    }

    public function revokeProctor(string $assignmentId, string $userId, ExamRoomAssignmentService $service): void
    {
        $this->assertCanManage();
        $data = $this->validate([
            'revokeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $assignment = ExamRoomAssignment::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($assignmentId);

        $service->revokeProctor(auth()->user(), $assignment, $userId, $data['revokeReason']);
        $this->reset('revokeReason');
        session()->flash('centralRoomAssignmentMessage', 'Penugasan Guru pengawas berhasil dicabut dan dicatat.');
    }

    private function assertCanManage(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
                && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $user->hasPermission('exam.proctor.assign'),
            403,
            'Hanya Admin Madrasah berizin yang dapat mengelola ruang dan penugasan Proktor ujian terpusat.'
        );
    }

    public function render()
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;

        $exams = Exam::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('category', Exam::CENTRALIZED_CATEGORIES)
            ->where('is_centralized', true)
            ->where('status', 'PUBLISHED')
            ->whereNotNull('locked_at')
            ->whereNotNull('end_at')
            ->where('end_at', '>', now())
            ->orderBy('start_at')
            ->get(['id', 'title', 'category', 'start_at', 'end_at']);

        $rooms = Room::query()->where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'capacity']);

        $proctors = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereHas('teacherProfile')
            ->orderBy('name')->get(['id', 'name', 'email']);

        $assignments = ExamRoomAssignment::query()->where('tenant_id', $tenantId)
            ->with([
                'exam:id,title,category,is_centralized',
                'room:id,code,name,capacity',
                'activeProctors.user:id,name,email',
            ])
            ->orderBy('scheduled_start')->limit(200)->get();

        return view('livewire.admin.central-exam-room-assignment-management', compact(
            'exams', 'rooms', 'proctors', 'assignments'
        ))->layout('layouts.app');
    }
}
