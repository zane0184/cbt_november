<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamParticipantRoomAssignment;
use App\Models\ExamProctorAssignment;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ExamSessionRoom;
use App\Models\ExamSessionRoomRombel;
use App\Models\Rombel;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamRoomSessionService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function createRoom(User $actor, array $data): ExamRoom
    {
        $this->assertAdmin($actor);
        $validated = validator($data, [
            'code' => ['required', 'string', 'max:50', Rule::unique('exam_rooms', 'code')->where('tenant_id', $actor->tenant_id)],
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $validated) {
            $room = ExamRoom::create([
                ...$validated,
                'tenant_id' => $actor->tenant_id,
                'status' => 'ACTIVE',
            ]);

            $this->auditService->log('EXAM_ROOM_CREATE', 'ExamRoom', $room->id, null, $room->only([
                'tenant_id', 'code', 'name', 'location', 'capacity', 'status',
            ]));

            return $room;
        });
    }

    public function updateRoom(User $actor, string $roomId, array $data): ExamRoom
    {
        $this->assertAdmin($actor);
        $validated = validator($data, [
            'code' => ['required', 'string', 'max:50', Rule::unique('exam_rooms', 'code')
                ->where('tenant_id', $actor->tenant_id)->ignore($roomId)],
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])->validate();

        return DB::transaction(function () use ($actor, $roomId, $validated) {
            $room = ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($roomId)
                ->lockForUpdate()
                ->firstOrFail();

            $futureSessionExists = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_room_id', $room->id)
                ->where('status', 'ACTIVE')
                ->whereHas('session', function ($query) {
                    $query->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                        ->where('ends_at', '>', now());
                })
                ->exists();

            if ($validated['status'] === 'INACTIVE' && $futureSessionExists) {
                throw ValidationException::withMessages([
                    'status' => 'Ruang tidak dapat dinonaktifkan selama masih digunakan sesi yang belum selesai.',
                ]);
            }

            $overrideExceedsCapacity = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_room_id', $room->id)
                ->where('status', 'ACTIVE')
                ->where('capacity_override', '>', $validated['capacity'])
                ->whereHas('session', fn ($query) => $query->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                    ->where('ends_at', '>', now()))
                ->exists();

            if ($overrideExceedsCapacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Kapasitas baru tidak boleh lebih kecil daripada override kapasitas sesi yang masih aktif.',
                ]);
            }

            $maxAssigned = DB::table('exam_session_rooms as esr')
                ->join('exam_sessions as es', function ($join) {
                    $join->on('es.id', '=', 'esr.exam_session_id')
                        ->on('es.tenant_id', '=', 'esr.tenant_id')
                        ->on('es.exam_id', '=', 'esr.exam_id');
                })
                ->join('exam_participant_room_assignments as epa', function ($join) {
                    $join->on('epa.exam_session_room_id', '=', 'esr.id')
                        ->on('epa.tenant_id', '=', 'esr.tenant_id')
                        ->on('epa.exam_id', '=', 'esr.exam_id');
                })
                ->where('esr.tenant_id', $actor->tenant_id)
                ->where('esr.exam_room_id', $room->id)
                ->where('esr.status', 'ACTIVE')
                ->whereIn('es.status', ['SCHEDULED', 'ACTIVE'])
                ->where('es.ends_at', '>', now())
                ->where('epa.status', 'ACTIVE')
                ->groupBy('esr.id')
                ->selectRaw('COUNT(epa.id) AS assigned_count')
                ->get()
                ->max('assigned_count') ?? 0;

            if ((int) $validated['capacity'] < (int) $maxAssigned) {
                throw ValidationException::withMessages([
                    'capacity' => 'Kapasitas baru lebih kecil daripada jumlah peserta yang sudah ditempatkan pada salah satu sesi.',
                ]);
            }

            $old = $room->only(['code', 'name', 'location', 'capacity', 'status']);
            $room->update($validated);

            $this->auditService->log('EXAM_ROOM_UPDATE', 'ExamRoom', $room->id, $old, $room->only([
                'code', 'name', 'location', 'capacity', 'status',
            ]));

            return $room->fresh();
        }, 3);
    }

    public function createSession(
        User $actor,
        string $examId,
        string $name,
        int $sessionNumber,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt
    ): ExamSession {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $examId, $name, $sessionNumber, $startsAt, $endsAt) {
            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($examId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertSchedulableExam($exam);
            $this->validateSessionWindow($exam, $startsAt, $endsAt);

            if ($sessionNumber < 1 || mb_strlen(trim($name)) < 1 || mb_strlen($name) > 120) {
                throw ValidationException::withMessages([
                    'session' => 'Nama sesi wajib diisi dan nomor sesi harus lebih besar dari nol.',
                ]);
            }

            if (ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('session_number', $sessionNumber)
                ->exists()) {
                throw ValidationException::withMessages([
                    'sessionNumber' => 'Nomor sesi sudah digunakan pada ujian ini.',
                ]);
            }

            $session = ExamSession::create([
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $exam->id,
                'name' => trim($name),
                'session_number' => $sessionNumber,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'schedule_revision' => 1,
                'status' => 'SCHEDULED',
            ]);

            $this->auditService->log('EXAM_SESSION_CREATE', 'ExamSession', $session->id, null, $session->only([
                'tenant_id', 'exam_id', 'name', 'session_number', 'starts_at', 'ends_at', 'schedule_revision', 'status',
            ]));

            return $session;
        });
    }

    public function attachRoom(
        User $actor,
        string $examId,
        string $sessionId,
        string $roomId,
        ?int $capacityOverride = null
    ): ExamSessionRoom {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $examId, $sessionId, $roomId, $capacityOverride) {
            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($examId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertSchedulableExam($exam);

            // The room row is the mutex for cross-exam room-overlap checks.
            $room = ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($roomId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'roomId' => 'Ruang yang dipasangkan harus berstatus aktif.',
                ]);
            }

            $session = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->status !== 'ACTIVE' || $session->status !== 'SCHEDULED') {
                throw ValidationException::withMessages([
                    'room' => 'Hanya ruang aktif yang dapat dipasang pada sesi terjadwal.',
                ]);
            }

            if ($capacityOverride !== null
                && ($capacityOverride < 1 || $capacityOverride > $room->capacity)) {
                throw ValidationException::withMessages([
                    'capacityOverride' => 'Kapasitas sesi harus antara 1 dan kapasitas maksimum ruang.',
                ]);
            }

            $overlap = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_room_id', $room->id)
                ->where('status', 'ACTIVE')
                ->whereHas('session', function ($query) use ($session) {
                    $query->where('id', '<>', $session->id)
                        ->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                        ->where('starts_at', '<', $session->ends_at)
                        ->where('ends_at', '>', $session->starts_at);
                })
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'room' => 'Ruang sudah digunakan pada sesi lain dengan waktu yang bertumpang tindih.',
                ]);
            }

            $existing = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_session_id', $session->id)
                ->where('exam_room_id', $room->id)
                ->first();

            if ($existing) {
                $scopeChanged = $existing->status !== 'ACTIVE'
                    || $existing->capacity_override !== $capacityOverride;
                if ($scopeChanged) {
                    $existing->update([
                        'status' => 'ACTIVE',
                        'capacity_override' => $capacityOverride,
                    ]);
                    $this->bumpScheduleRevision($actor, $session, 'Pasangan ruang-sesi diaktifkan atau kapasitasnya diubah.');
                }
                return $existing->fresh()->load(['room', 'session']);
            }

            $sessionRoom = ExamSessionRoom::create([
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $exam->id,
                'exam_session_id' => $session->id,
                'exam_room_id' => $room->id,
                'capacity_override' => $capacityOverride,
                'status' => 'ACTIVE',
            ]);

            $this->bumpScheduleRevision($actor, $session, 'Ruang ujian ditambahkan pada sesi.');

            $this->auditService->log('EXAM_SESSION_ROOM_ATTACH', 'ExamSessionRoom', $sessionRoom->id, null, [
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $exam->id,
                'exam_session_id' => $session->id,
                'exam_room_id' => $room->id,
                'capacity_override' => $capacityOverride,
            ]);

            return $sessionRoom->load(['room', 'session']);
        });
    }

    public function mapRombel(User $actor, string $sessionRoomId, string $rombelId): ExamSessionRoomRombel
    {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $sessionRoomId, $rombelId) {
            $scope = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($sessionRoomId)
                ->firstOrFail();

            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($scope->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            $roomId = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionRoomId)
                ->value('exam_room_id');
            $room = ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($roomId)
                ->lockForUpdate()
                ->firstOrFail();

            $sessionRoom = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionRoomId)
                ->lockForUpdate()
                ->firstOrFail();

            $session = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionRoom->exam_session_id)
                ->lockForUpdate()
                ->firstOrFail();

            $rombel = Rombel::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($rombelId)
                ->lockForUpdate()
                ->firstOrFail();

            $assessment = $exam->assessment;
            if (!$assessment || (string) $assessment->academic_year_id !== (string) $rombel->academic_year_id) {
                throw ValidationException::withMessages([
                    'rombel' => 'Rombel harus berasal dari tahun ajaran yang sama dengan asesmen ujian.',
                ]);
            }

            if ($room->status !== 'ACTIVE' || $rombel->status !== 'ACTIVE'
                || $sessionRoom->status !== 'ACTIVE' || $session->status !== 'SCHEDULED') {
                throw ValidationException::withMessages([
                    'sessionRoom' => 'Pemetaan hanya dapat dilakukan pada ruang dan rombel aktif serta sesi yang belum berjalan.',
                ]);
            }

            $existingMapping = ExamSessionRoomRombel::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('rombel_id', $rombel->id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if ($existingMapping && (string) $existingMapping->exam_session_room_id !== (string) $sessionRoom->id) {
                throw ValidationException::withMessages([
                    'rombel' => 'Rombel sudah dipetakan ke sesi-ruang lain. Lepaskan pemetaan lama sebelum memindahkannya.',
                ]);
            }

            $participants = ExamParticipant::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('rombel_id', $rombel->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $participantIds = $participants->pluck('id');
            $conflictingPlacement = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('participant_id', $participantIds)
                ->where('status', 'ACTIVE')
                ->where('exam_session_room_id', '<>', $sessionRoom->id)
                ->exists();

            if ($conflictingPlacement) {
                throw ValidationException::withMessages([
                    'rombel' => 'Sebagian peserta sudah ditempatkan pada sesi-ruang lain. Selesaikan pemetaan lama terlebih dahulu.',
                ]);
            }

            $alreadyPlaced = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('exam_session_room_id', $sessionRoom->id)
                ->where('status', 'ACTIVE')
                ->count();

            $placedParticipantIds = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('participant_id', $participantIds)
                ->where('status', 'ACTIVE')
                ->pluck('participant_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $unplaced = $participants->reject(
                fn (ExamParticipant $participant) => in_array((string) $participant->id, $placedParticipantIds, true)
            );

            $capacity = $sessionRoom->capacity_override ?: $room->capacity;
            if ($alreadyPlaced + $unplaced->count() > (int) $capacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Jumlah peserta melebihi kapasitas ruang untuk sesi ini.',
                ]);
            }

            $mappingCreated = !$existingMapping;
            $mapping = $existingMapping ?: ExamSessionRoomRombel::create([
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $exam->id,
                'exam_session_room_id' => $sessionRoom->id,
                'rombel_id' => $rombel->id,
                'status' => 'ACTIVE',
                'assigned_by' => $actor->id,
            ]);

            foreach ($unplaced as $participant) {
                ExamParticipantRoomAssignment::create([
                    'tenant_id' => $actor->tenant_id,
                    'exam_id' => $exam->id,
                    'participant_id' => $participant->id,
                    'exam_session_room_id' => $sessionRoom->id,
                    'status' => 'ACTIVE',
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                ]);
            }

            if ($mappingCreated || $unplaced->isNotEmpty()) {
                $this->bumpScheduleRevision($actor, $session, 'Pemetaan rombel/peserta ke ruang sesi berubah.');
            }

            $this->auditService->log('EXAM_SESSION_ROOM_ROMBEL_MAP', 'ExamSessionRoomRombel', $mapping->id, null, [
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $exam->id,
                'exam_session_room_id' => $sessionRoom->id,
                'rombel_id' => $rombel->id,
                'participant_count' => $unplaced->count(),
            ]);

            return $mapping->load(['sessionRoom', 'rombel']);
        });
    }

    public function releaseRombelMapping(User $actor, string $mappingId, string $reason): void
    {
        $this->assertAdmin($actor);
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => 'Alasan pelepasan wajib berisi 10–500 karakter.']);
        }

        DB::transaction(function () use ($actor, $mappingId, $reason) {
            $snapshot = ExamSessionRoomRombel::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($mappingId)
                ->firstOrFail();

            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($snapshot->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            $roomId = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($snapshot->exam_session_room_id)
                ->value('exam_room_id');

            ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($roomId)
                ->lockForUpdate()
                ->firstOrFail();

            $sessionRoom = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($snapshot->exam_session_room_id)
                ->lockForUpdate()
                ->firstOrFail();

            $session = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionRoom->exam_session_id)
                ->lockForUpdate()
                ->firstOrFail();

            $mapping = ExamSessionRoomRombel::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($mappingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($mapping->status === 'RELEASED') {
                return;
            }

            if ($session->status !== 'SCHEDULED' || $sessionRoom->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'mapping' => 'Pemetaan rombel hanya dapat dilepaskan sebelum sesi mulai.',
                ]);
            }

            $participantIds = ExamParticipant::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('rombel_id', $mapping->rombel_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $assignments = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('participant_id', $participantIds)
                ->where('exam_session_room_id', $sessionRoom->id)
                ->where('status', 'ACTIVE')
                ->orderBy('participant_id')
                ->lockForUpdate()
                ->get();

            $hasActiveAttempt = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('id', $assignments->pluck('id'))
                ->whereHas('participant.attempts', fn ($query) => $query->where('status', 'IN_PROGRESS'))
                ->exists();

            if ($hasActiveAttempt) {
                throw ValidationException::withMessages([
                    'mapping' => 'Pemetaan tidak dapat dilepaskan ketika peserta sedang mengerjakan ujian.',
                ]);
            }

            foreach ($assignments as $assignment) {
                $assignment->update([
                    'status' => 'RELEASED',
                    'released_at' => now(),
                    'released_by' => $actor->id,
                    'release_reason' => $reason,
                ]);
            }

            $old = $mapping->only(['status', 'released_at', 'released_by', 'release_reason']);
            $mapping->update([
                'status' => 'RELEASED',
                'released_at' => now(),
                'released_by' => $actor->id,
                'release_reason' => $reason,
            ]);

            $this->bumpScheduleRevision($actor, $session, 'Pemetaan rombel dari ruang sesi dilepaskan.');

            $this->auditService->log('EXAM_SESSION_ROOM_ROMBEL_RELEASE', 'ExamSessionRoomRombel', $mapping->id, $old, [
                'status' => 'RELEASED',
                'released_by' => $actor->id,
                'release_reason' => $reason,
                'released_participant_count' => $assignments->count(),
            ]);
        });
    }

    public function cancelSession(User $actor, string $sessionId, string $reason): ExamSession
    {
        $this->assertAdmin($actor);
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan pembatalan wajib berisi 10–500 karakter.',
            ]);
        }

        return DB::transaction(function () use ($actor, $sessionId, $reason) {
            $snapshot = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($sessionId)
                ->firstOrFail();

            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($snapshot->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            $sessionRoomIds = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('exam_session_id', $sessionId)
                ->where('status', 'ACTIVE')
                ->orderBy('exam_room_id')
                ->pluck('id');

            $roomIds = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereIn('id', $sessionRoomIds)
                ->orderBy('exam_room_id')
                ->pluck('exam_room_id')
                ->unique()
                ->values();

            $lockedRooms = ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereIn('id', $roomIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedRooms->count() !== $roomIds->count()) {
                throw ValidationException::withMessages([
                    'room' => 'Salah satu ruang sesi tidak lagi tersedia pada tenant ini.',
                ]);
            }

            $session = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'SCHEDULED') {
                throw ValidationException::withMessages([
                    'session' => 'Hanya sesi yang belum dimulai yang dapat dibatalkan.',
                ]);
            }

            $hasActiveAttempt = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_session_room_id', $sessionRoomIds)
                ->where('status', 'ACTIVE')
                ->whereHas('participant.attempts', fn ($query) => $query->where('status', 'IN_PROGRESS'))
                ->exists();

            if ($hasActiveAttempt) {
                throw ValidationException::withMessages([
                    'session' => 'Sesi tidak dapat dibatalkan ketika masih ada peserta yang sedang mengerjakan ujian.',
                ]);
            }

            $assignments = ExamProctorAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_session_room_id', $sessionRoomIds)
                ->whereNull('revoked_at')
                ->whereIn('status', ['PENDING', 'CONFIRMED', 'NEEDS_REVIEW'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $old = $assignment->only(['status', 'revoked_at', 'revoked_by', 'revoke_reason']);
                $assignment->update([
                    'status' => 'REVOKED',
                    'revoked_at' => now(),
                    'revoked_by' => $actor->id,
                    'revoke_reason' => $reason,
                ]);
                $this->auditService->log('EXAM_PROCTOR_ASSIGNMENT_REVOKE', 'ExamProctorAssignment', $assignment->id, $old, [
                    'status' => 'REVOKED',
                    'revoked_by' => $actor->id,
                    'revoke_reason' => $reason,
                    'source' => 'SESSION_CANCELLED',
                ]);
            }

            $participantAssignments = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_session_room_id', $sessionRoomIds)
                ->where('status', 'ACTIVE')
                ->orderBy('participant_id')
                ->lockForUpdate()
                ->get();

            foreach ($participantAssignments as $assignment) {
                $assignment->update([
                    'status' => 'RELEASED',
                    'released_at' => now(),
                    'released_by' => $actor->id,
                    'release_reason' => $reason,
                ]);
            }

            $mappings = ExamSessionRoomRombel::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_session_room_id', $sessionRoomIds)
                ->where('status', 'ACTIVE')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($mappings as $mapping) {
                $mapping->update([
                    'status' => 'RELEASED',
                    'released_at' => now(),
                    'released_by' => $actor->id,
                    'release_reason' => $reason,
                ]);
            }

            ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('id', $sessionRoomIds)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'INACTIVE']);

            $oldSession = $session->only(['status', 'schedule_revision']);
            $session->update([
                'status' => 'CANCELLED',
                'schedule_revision' => (int) $session->schedule_revision + 1,
            ]);

            $window = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                ->selectRaw('MIN(starts_at) AS starts_at, MAX(ends_at) AS ends_at')
                ->first();

            if ($window?->starts_at && $window?->ends_at) {
                $exam->update(['start_at' => $window->starts_at, 'end_at' => $window->ends_at]);
            }

            $this->auditService->log('EXAM_SESSION_CANCEL', 'ExamSession', $session->id, $oldSession, [
                'status' => 'CANCELLED',
                'schedule_revision' => $session->schedule_revision,
                'reason' => $reason,
                'released_participant_count' => $participantAssignments->count(),
                'revoked_proctor_assignment_count' => $assignments->count(),
            ]);

            return $session->fresh()->load('sessionRooms');
        }, 3);
    }

    public function rescheduleSession(
        User $actor,
        string $sessionId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        string $reason
    ): ExamSession {
        $this->assertAdmin($actor);
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => 'Alasan perubahan jadwal wajib berisi 10–500 karakter.']);
        }

        return DB::transaction(function () use ($actor, $sessionId, $startsAt, $endsAt, $reason) {
            $snapshot = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($sessionId)
                ->firstOrFail();

            $exam = Exam::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($snapshot->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lock all physical rooms before the session row. attachRoom uses
            // the same order, so concurrent room changes are serialized.
            $roomIds = ExamSessionRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('exam_session_id', $sessionId)
                ->where('status', 'ACTIVE')
                ->orderBy('exam_room_id')
                ->pluck('exam_room_id')
                ->unique()
                ->values();

            $lockedRooms = ExamRoom::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereIn('id', $roomIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedRooms->count() !== $roomIds->count()) {
                throw ValidationException::withMessages(['room' => 'Salah satu ruang sesi tidak lagi tersedia pada tenant ini.']);
            }

            $session = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertSchedulableExam($exam);
            if ($session->status !== 'SCHEDULED') {
                throw ValidationException::withMessages(['session' => 'Sesi yang sudah berjalan atau selesai tidak dapat dijadwalkan ulang.']);
            }
            $this->validateSessionWindow($exam, $startsAt, $endsAt);

            foreach ($roomIds as $roomId) {
                $overlap = ExamSessionRoom::query()
                    ->where('tenant_id', $actor->tenant_id)
                    ->where('exam_room_id', $roomId)
                    ->where('status', 'ACTIVE')
                    ->whereHas('session', function ($query) use ($session, $startsAt, $endsAt) {
                        $query->where('id', '<>', $session->id)
                            ->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                            ->where('starts_at', '<', $endsAt)
                            ->where('ends_at', '>', $startsAt);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'session' => 'Perubahan jadwal membuat ruang bertumpang tindih dengan sesi lain.',
                    ]);
                }
            }

            $sessionRoomIds = $session->sessionRooms()->where('status', 'ACTIVE')->pluck('id');
            $hasActiveAttempt = ExamParticipantRoomAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_session_room_id', $sessionRoomIds)
                ->where('status', 'ACTIVE')
                ->whereHas('participant.attempts', fn ($query) => $query->where('status', 'IN_PROGRESS'))
                ->exists();

            if ($hasActiveAttempt) {
                throw ValidationException::withMessages(['session' => 'Jadwal tidak dapat diubah ketika peserta pada sesi ini sedang mengerjakan ujian.']);
            }

            $old = $session->only(['starts_at', 'ends_at', 'schedule_revision']);
            $session->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $this->bumpScheduleRevision($actor, $session, $reason);

            $window = ExamSession::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('status', ['SCHEDULED', 'ACTIVE'])
                ->selectRaw('MIN(starts_at) AS starts_at, MAX(ends_at) AS ends_at')
                ->first();

            if ($window?->starts_at && $window?->ends_at) {
                $exam->update(['start_at' => $window->starts_at, 'end_at' => $window->ends_at]);
            }

            $this->auditService->log('EXAM_SESSION_RESCHEDULE', 'ExamSession', $session->id, $old, [
                'starts_at' => $session->starts_at,
                'ends_at' => $session->ends_at,
                'schedule_revision' => $session->fresh()->schedule_revision,
                'reason' => $reason,
            ]);

            return $session->fresh()->load('sessionRooms');
        });
    }

    private function bumpScheduleRevision(User $actor, ExamSession $session, string $reason): void
    {
        $oldRevision = (int) $session->schedule_revision;
        $session->update(['schedule_revision' => $oldRevision + 1]);

        $roomIds = $session->sessionRooms()->pluck('id');
        $affected = ExamProctorAssignment::query()
            ->where('tenant_id', $actor->tenant_id)
            ->where('exam_id', $session->exam_id)
            ->whereIn('exam_session_room_id', $roomIds)
            ->whereNull('revoked_at')
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->lockForUpdate()
            ->get();

        foreach ($affected as $assignment) {
            $old = $assignment->only(['status', 'schedule_revision']);
            $assignment->update(['status' => 'NEEDS_REVIEW']);

            $this->auditService->log('EXAM_PROCTOR_ASSIGNMENT_NEEDS_REVIEW', 'ExamProctorAssignment', $assignment->id, $old, [
                'status' => 'NEEDS_REVIEW',
                'schedule_revision' => $assignment->schedule_revision,
                'session_schedule_revision' => $oldRevision + 1,
                'reason' => $reason,
            ]);
        }

        $this->auditService->log('EXAM_SESSION_SCOPE_REVISION', 'ExamSession', $session->id, [
            'schedule_revision' => $oldRevision,
        ], [
            'schedule_revision' => $oldRevision + 1,
            'reason' => $reason,
        ]);
    }

    private function assertAdmin(User $actor): void
    {
        if (!$actor->tenant_id || !$actor->hasRole('ADMIN_MADRASAH') || !$actor->hasPermission('exam.proctor.assign')) {
            abort(403, 'Hanya Admin Madrasah berizin yang dapat mengelola metadata ruang dan sesi ujian.');
        }
    }

    private function assertSchedulableExam(Exam $exam): void
    {
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at || !$exam->start_at || !$exam->end_at) {
            throw ValidationException::withMessages([
                'exam' => 'Ruang dan sesi hanya dapat diatur untuk ujian yang sudah diterbitkan dan dikunci.',
            ]);
        }
    }

    private function validateSessionWindow(Exam $exam, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        if ($startsAt->greaterThanOrEqualTo($endsAt)
            || $startsAt->lessThan($exam->start_at)
            || $endsAt->greaterThan($exam->end_at)) {
            throw ValidationException::withMessages([
                'session' => 'Rentang sesi harus berada di dalam jadwal ujian dan waktu mulai harus sebelum waktu selesai.',
            ]);
        }
    }
}
