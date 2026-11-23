<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use App\Models\ExamRoomProctor;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExamRoomAssignmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Create one room/session and assign one or more Guru users.
     * Multiple Guru in the same room/session are intentionally supported.
     *
     * @param array<int, string>|array<string, string> $proctors User IDs or user ID => role_label.
     */
    public function create(
        User $actor,
        Exam $exam,
        Room $room,
        array $proctors,
        int $sessionNumber,
        CarbonInterface $scheduledStart,
        CarbonInterface $scheduledEnd,
        bool $mergeRoom = false
    ): ExamRoomAssignment {
        return DB::transaction(function () use (
            $actor, $exam, $room, $proctors, $sessionNumber,
            $scheduledStart, $scheduledEnd, $mergeRoom
        ) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()->where('tenant_id', $tenantId)
                ->whereKey($exam->id)->lockForUpdate()->firstOrFail();

            Gate::forUser($actor)->authorize('manageCentralRoomAssignments', $lockedExam);

            if (!$lockedExam->isCentralized()) {
                throw ValidationException::withMessages([
                    'exam' => 'Penugasan ruang melalui Admin hanya berlaku untuk ujian terpusat ASTS, ASAS, ASAT, UM, atau SIMULASI_AN.',
                ]);
            }

            $start = \Illuminate\Support\Carbon::parse($scheduledStart);
            $end = \Illuminate\Support\Carbon::parse($scheduledEnd);
            if ($sessionNumber < 1 || !$start->lt($end)) {
                throw ValidationException::withMessages(['scheduledStart' => 'Nomor sesi dan rentang jadwal tidak valid.']);
            }
            if (($lockedExam->start_at && $start->lt($lockedExam->start_at))
                || ($lockedExam->end_at && $end->gt($lockedExam->end_at))) {
                throw ValidationException::withMessages(['scheduledEnd' => 'Jadwal ruang harus berada dalam rentang waktu ujian.']);
            }

            $lockedRoom = Room::query()->where('tenant_id', $tenantId)
                ->whereKey($room->id)->lockForUpdate()->firstOrFail();
            if (!$lockedRoom->is_active) {
                throw ValidationException::withMessages(['room' => 'Ruang ujian tidak aktif.']);
            }

            $normalizedProctors = [];
            foreach ($proctors as $key => $value) {
                $userId = is_string($key) && !is_int($key) ? $key : (string) $value;
                $roleLabel = is_string($key) && !is_int($key) ? (string) $value : 'proctor';
                if (!in_array($roleLabel, ['proctor', 'supervisor'], true)) {
                    throw ValidationException::withMessages(['proctors' => 'Label pengawas hanya boleh proctor atau supervisor.']);
                }
                $normalizedProctors[$userId] = $roleLabel;
            }
            if ($normalizedProctors === []) {
                throw ValidationException::withMessages(['proctors' => 'Minimal satu Guru harus ditugaskan pada ruang/sesi.']);
            }

            // Lock Guru rows in stable ID order to serialize concurrent assignments
            // for the same person, including assignments to different rooms.
            $guruRows = User::query()->where('tenant_id', $tenantId)
                ->whereIn('id', array_keys($normalizedProctors))
                ->whereNull('deleted_at')
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($guruRows->count() !== count($normalizedProctors)) {
                throw ValidationException::withMessages(['proctors' => 'Salah satu akun Guru tidak ditemukan pada madrasah ini.']);
            }

            foreach ($guruRows as $guru) {
                if (!$guru->roles()->where('name', 'GURU')->exists() || !$guru->teacherProfile()->exists()) {
                    throw ValidationException::withMessages(['proctors' => 'Semua pengawas harus merupakan Guru aktif dengan profil Guru pada madrasah ini.']);
                }

                $teacherConflict = DB::table('exam_room_proctors as erp')
                    ->join('exam_room_assignments as era', function ($join) {
                        $join->on('era.id', '=', 'erp.exam_room_assignment_id')
                            ->on('era.tenant_id', '=', 'erp.tenant_id');
                    })
                    ->where('erp.tenant_id', $tenantId)
                    ->where('erp.user_id', $guru->id)
                    ->where('erp.status', 'ACTIVE')
                    ->whereNull('erp.revoked_at')
                    ->where('era.scheduled_start', '<', $end)
                    ->where('era.scheduled_end', '>', $start)
                    ->exists();

                if ($teacherConflict) {
                    throw ValidationException::withMessages([
                        'proctors' => 'Guru '.$guru->name.' sudah ditugaskan pada ruang/sesi lain dengan waktu yang beririsan.',
                    ]);
                }
            }

            // Room overlap is blocked unless BOTH assignments explicitly opt into
            // merge-room and have exactly the same scheduled interval.
            $roomConflicts = ExamRoomAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('room_id', $lockedRoom->id)
                ->where('scheduled_start', '<', $end)
                ->where('scheduled_end', '>', $start)
                ->lockForUpdate()->get();

            foreach ($roomConflicts as $conflict) {
                $sameInterval = $conflict->scheduled_start->equalTo($start)
                    && $conflict->scheduled_end->equalTo($end);
                if (!$mergeRoom || !$conflict->merge_room || !$sameInterval) {
                    throw ValidationException::withMessages([
                        'room' => 'Ruang sudah digunakan pada waktu yang beririsan. Gabung Ruang hanya mengizinkan jadwal yang identik dan disetujui pada kedua penugasan.',
                    ]);
                }
            }

            $existing = ExamRoomAssignment::query()->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->where('room_id', $lockedRoom->id)
                ->where('session_number', $sessionNumber)
                ->lockForUpdate()->first();
            if ($existing) {
                throw ValidationException::withMessages(['sessionNumber' => 'Ruang dan nomor sesi ini sudah terdaftar pada ujian tersebut.']);
            }

            $assignment = ExamRoomAssignment::create([
                'tenant_id' => $tenantId,
                'exam_id' => $lockedExam->id,
                'room_id' => $lockedRoom->id,
                'session_number' => $sessionNumber,
                'session_token' => $this->generateUniqueSessionToken($tenantId),
                'token_expires_at' => $end,
                'scheduled_start' => $start,
                'scheduled_end' => $end,
                'merge_room' => $mergeRoom,
                'created_by' => $actor->id,
            ]);

            foreach ($normalizedProctors as $userId => $roleLabel) {
                ExamRoomProctor::create([
                    'tenant_id' => $tenantId,
                    'exam_room_assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'role_label' => $roleLabel,
                    'status' => 'ACTIVE',
                    'assigned_by' => $actor->id,
                ]);
            }

            $this->auditService->log(
                'EXAM_ROOM_ASSIGNMENT_CREATE',
                'ExamRoomAssignment',
                $assignment->id,
                null,
                [
                    'tenant_id' => $tenantId,
                    'exam_id' => $lockedExam->id,
                    'room_id' => $lockedRoom->id,
                    'session_number' => $sessionNumber,
                    'proctor_user_ids' => array_keys($normalizedProctors),
                    'scheduled_start' => $start,
                    'scheduled_end' => $end,
                    'merge_room' => $mergeRoom,
                ]
            );

            return $assignment->fresh()->load(['exam', 'room', 'activeProctors.user']);
        });
    }

    public function revokeProctor(User $actor, ExamRoomAssignment $assignment, string $userId, string $reason): ExamRoomProctor
    {
        return DB::transaction(function () use ($actor, $assignment, $userId, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $lockedAssignment = ExamRoomAssignment::query()->where('tenant_id', $tenantId)
                ->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageProctors', $lockedAssignment);

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['reason' => 'Alasan pencabutan wajib berisi 10–1.000 karakter.']);
            }

            $membership = ExamRoomProctor::query()->where('tenant_id', $tenantId)
                ->where('exam_room_assignment_id', $lockedAssignment->id)
                ->where('user_id', $userId)->lockForUpdate()->firstOrFail();

            if ($membership->status === 'REVOKED' || $membership->revoked_at) {
                return $membership;
            }

            $old = $membership->only(['status', 'role_label', 'revoked_at', 'revoked_by', 'revoke_reason']);
            $membership->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revoke_reason' => $reason,
            ]);

            $this->auditService->log(
                'EXAM_ROOM_PROCTOR_REVOKE',
                'ExamRoomProctor',
                $membership->id,
                $old,
                $membership->fresh()->only(['tenant_id', 'exam_room_assignment_id', 'user_id', 'status', 'revoked_at', 'revoked_by', 'revoke_reason'])
            );

            return $membership->fresh();
        });
    }

    private function generateUniqueSessionToken(string $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $token = '';
            for ($i = 0; $i < 6; $i++) {
                $token .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $exists = DB::table('exam_room_assignments')
                ->where('tenant_id', $tenantId)
                ->where('session_token', $token)->exists();
            if (!$exists) return $token;
        }

        throw new \RuntimeException('Tidak dapat menghasilkan token sesi unik. Coba ulangi transaksi.');
    }
}
