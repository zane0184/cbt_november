<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ExamSessionRoom;
use App\Models\ExamSessionRoomRombel;
use App\Models\Rombel;
use App\Services\ExamRoomSessionService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ExamRoomSessionManagement extends Component
{
    public string $roomCode = '';
    public string $roomName = '';
    public string $roomLocation = '';
    public int $roomCapacity = 30;
    public string $roomStatus = 'ACTIVE';
    public string $editingRoomId = '';

    public string $selectedExamId = '';
    public string $sessionName = '';
    public int $sessionNumber = 1;
    public string $sessionStartsAt = '';
    public string $sessionEndsAt = '';

    public string $selectedSessionId = '';
    public string $selectedRoomId = '';
    public string $capacityOverride = '';

    public string $selectedSessionRoomId = '';
    public string $selectedRombelId = '';
    public string $releaseReason = '';
    public string $rescheduleReason = '';
    public string $cancelReason = '';

    public function boot(): void
    {
        $this->assertCanManage();
    }

    public function mount(): void
    {
        $this->assertCanManage();
        $this->sessionStartsAt = now()->addMinutes(10)->format('Y-m-d\\TH:i');
        $this->sessionEndsAt = now()->addHour()->format('Y-m-d\\TH:i');
    }

    public function updatedSelectedExamId(string $examId): void
    {
        $exam = Exam::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($examId)
            ->first();

        if ($exam?->start_at && $exam?->end_at) {
            $start = $exam->start_at->copy()->startOfMinute();
            if ($start->lessThan($exam->start_at)) {
                $start->addMinute();
            }
            $end = $exam->end_at->copy()->startOfMinute();

            $this->sessionStartsAt = $start->format('Y-m-d\\TH:i');
            $this->sessionEndsAt = $end->format('Y-m-d\\TH:i');
        }
    }

    public function selectSessionForReschedule(string $sessionId): void
    {
        $this->assertCanManage();
        $session = ExamSession::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($sessionId)
            ->firstOrFail();

        $this->selectedSessionId = $session->id;
        $start = $session->starts_at->copy()->startOfMinute();
        if ($start->lessThan($session->starts_at)) {
            $start->addMinute();
        }
        $end = $session->ends_at->copy()->startOfMinute();
        $this->sessionStartsAt = $start->format('Y-m-d\\TH:i');
        $this->sessionEndsAt = $end->format('Y-m-d\\TH:i');
        $this->rescheduleReason = '';
    }

    public function createRoom(): void
    {
        $this->editingRoomId = '';
        $this->saveRoom();
    }

    public function editRoom(string $roomId): void
    {
        $this->assertCanManage();
        $room = ExamRoom::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($roomId)
            ->firstOrFail();

        $this->editingRoomId = $room->id;
        $this->roomCode = $room->code;
        $this->roomName = $room->name;
        $this->roomLocation = $room->location ?? '';
        $this->roomCapacity = (int) $room->capacity;
        $this->roomStatus = $room->status;
    }

    public function cancelRoomEdit(): void
    {
        $this->reset('editingRoomId', 'roomCode', 'roomName', 'roomLocation');
        $this->roomCapacity = 30;
        $this->roomStatus = 'ACTIVE';
    }

    public function saveRoom(): void
    {
        $this->assertCanManage();
        $data = $this->validate([
            'roomCode' => ['required', 'string', 'max:50'],
            'roomName' => ['required', 'string', 'max:150'],
            'roomLocation' => ['nullable', 'string', 'max:255'],
            'roomCapacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'roomStatus' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $payload = [
            'code' => trim($data['roomCode']),
            'name' => trim($data['roomName']),
            'location' => filled($data['roomLocation']) ? trim($data['roomLocation']) : null,
            'capacity' => (int) $data['roomCapacity'],
            'status' => $data['roomStatus'],
        ];

        if ($this->editingRoomId !== '') {
            app(ExamRoomSessionService::class)->updateRoom(auth()->user(), $this->editingRoomId, $payload);
            session()->flash('examRoomSessionMessage', 'Metadata ruang berhasil diperbarui.');
        } else {
            app(ExamRoomSessionService::class)->createRoom(auth()->user(), $payload);
            session()->flash('examRoomSessionMessage', 'Ruang ujian berhasil ditambahkan.');
        }

        $this->cancelRoomEdit();
    }

    public function setRoomStatus(string $roomId, string $status): void
    {
        $this->assertCanManage();
        abort_unless(in_array($status, ['ACTIVE', 'INACTIVE'], true), 422);

        $room = ExamRoom::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($roomId)
            ->firstOrFail();

        app(ExamRoomSessionService::class)->updateRoom(auth()->user(), $room->id, [
            'code' => $room->code,
            'name' => $room->name,
            'location' => $room->location,
            'capacity' => (int) $room->capacity,
            'status' => $status,
        ]);

        session()->flash('examRoomSessionMessage', $status === 'ACTIVE'
            ? 'Ruang berhasil diaktifkan.'
            : 'Ruang berhasil dinonaktifkan.');
    }

    public function createSession(): void
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedExamId' => ['required', 'uuid', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
            'sessionName' => ['required', 'string', 'max:120'],
            'sessionNumber' => ['required', 'integer', 'min:1', 'max:65535'],
            'sessionStartsAt' => ['required', 'date'],
            'sessionEndsAt' => ['required', 'date', 'after:sessionStartsAt'],
        ]);

        app(ExamRoomSessionService::class)->createSession(
            auth()->user(),
            $data['selectedExamId'],
            trim($data['sessionName']),
            (int) $data['sessionNumber'],
            Carbon::parse($data['sessionStartsAt']),
            Carbon::parse($data['sessionEndsAt'])
        );

        $this->reset('sessionName');
        $this->sessionNumber = 1;
        session()->flash('examRoomSessionMessage', 'Sesi ujian berhasil dibuat.');
    }

    public function attachRoom(): void
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedExamId' => ['required', 'uuid', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
            'selectedSessionId' => ['required', 'uuid', Rule::exists('exam_sessions', 'id')->where('tenant_id', $tenantId)->where('exam_id', $this->selectedExamId)],
            'selectedRoomId' => ['required', 'uuid', Rule::exists('exam_rooms', 'id')->where('tenant_id', $tenantId)],
            'capacityOverride' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        app(ExamRoomSessionService::class)->attachRoom(
            auth()->user(),
            $data['selectedExamId'],
            $data['selectedSessionId'],
            $data['selectedRoomId'],
            $data['capacityOverride'] !== '' ? (int) $data['capacityOverride'] : null
        );

        $this->reset('capacityOverride');
        session()->flash('examRoomSessionMessage', 'Ruang berhasil dipasangkan ke sesi.');
    }

    public function mapRombel(): void
    {
        $this->assertCanManage();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedSessionRoomId' => ['required', 'uuid', Rule::exists('exam_session_rooms', 'id')->where('tenant_id', $tenantId)],
            'selectedRombelId' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $tenantId)],
        ]);

        app(ExamRoomSessionService::class)->mapRombel(
            auth()->user(),
            $data['selectedSessionRoomId'],
            $data['selectedRombelId']
        );

        session()->flash('examRoomSessionMessage', 'Rombel dan peserta yang memiliki snapshot asal telah dipetakan.');
    }

    public function releaseMapping(string $mappingId): void
    {
        $this->assertCanManage();
        $data = $this->validate(['releaseReason' => ['required', 'string', 'min:10', 'max:500']]);
        app(ExamRoomSessionService::class)->releaseRombelMapping(
            auth()->user(),
            $mappingId,
            $data['releaseReason']
        );
        $this->reset('releaseReason');
        session()->flash('examRoomSessionMessage', 'Pemetaan rombel dilepaskan; histori tetap disimpan.');
    }

    public function cancelSession(string $sessionId): void
    {
        $this->assertCanManage();
        $data = $this->validate([
            'cancelReason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        app(ExamRoomSessionService::class)->cancelSession(
            auth()->user(),
            $sessionId,
            $data['cancelReason']
        );

        $this->reset('cancelReason');
        if ($this->selectedSessionId === $sessionId) {
            $this->selectedSessionId = '';
        }
        session()->flash('examRoomSessionMessage', 'Sesi dibatalkan. Assignment Proktor dicabut dan histori penempatan dipertahankan.');
    }

    public function rescheduleSession(string $sessionId): void
    {
        $this->assertCanManage();
        $data = $this->validate([
            'sessionStartsAt' => ['required', 'date'],
            'sessionEndsAt' => ['required', 'date', 'after:sessionStartsAt'],
            'rescheduleReason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        app(ExamRoomSessionService::class)->rescheduleSession(
            auth()->user(),
            $sessionId,
            Carbon::parse($data['sessionStartsAt']),
            Carbon::parse($data['sessionEndsAt']),
            $data['rescheduleReason']
        );

        $this->reset('rescheduleReason');
        session()->flash('examRoomSessionMessage', 'Jadwal diperbarui. Assignment Proktor terdampak berstatus NEEDS_REVIEW dan harus dikonfirmasi ulang.');
    }

    private function assertCanManage(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->hasRole('ADMIN_MADRASAH')
                && $user->hasPermission('exam.proctor.assign'),
            403,
            'Hanya Admin Madrasah berizin yang dapat mengelola metadata ruang dan sesi.'
        );
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $exams = Exam::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'PUBLISHED')
            ->whereNotNull('locked_at')
            ->whereNotNull('end_at')
            ->where('end_at', '>', now())
            ->with('assessment:id,academic_year_id')
            ->orderBy('start_at')
            ->get(['id', 'assessment_id', 'title', 'start_at', 'end_at']);

        $rooms = ExamRoom::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('status')
            ->orderBy('code')
            ->get();

        $sessions = ExamSession::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'exam:id,title',
                'sessionRooms.room',
                'sessionRooms.participantAssignments',
                'sessionRooms.rombelMappings.rombel',
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $selectedExam = $exams->firstWhere('id', $this->selectedExamId);
        $rombels = Rombel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->when($selectedExam?->assessment?->academic_year_id, fn ($query, $yearId) => $query->where('academic_year_id', $yearId))
            ->orderBy('name')
            ->get();

        $sessionRooms = ExamSessionRoom::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with(['exam:id,title', 'session:id,exam_id,name,session_number,schedule_revision,starts_at,ends_at', 'room:id,code,name,capacity'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('livewire.admin.exam-room-session-management', compact(
            'exams', 'rooms', 'sessions', 'rombels', 'sessionRooms'
        ))->layout('layouts.app');
    }
}
