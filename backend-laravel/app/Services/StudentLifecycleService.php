<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\Rombel;
use App\Models\StudentAcademicEvent;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLifecycleService
{
    public function enroll(string $tenantId, string $actorId, string $studentId, string $rombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $rombelId, $effectiveDate, $reason) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $this->assertStudentEligible($tenantId, $student);
            $rombel = $this->lockRombel($tenantId, $rombelId, true);

            if (DB::table('rombel_student')->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)->where('academic_year_id', $rombel->academic_year_id)->exists()) {
                throw ValidationException::withMessages(['selectedStudentId' => 'Siswa sudah memiliki penempatan pada tahun pelajaran ini. Gunakan fitur mutasi jika ingin pindah rombel.']);
            }

            $this->attach($tenantId, $student, $rombel);
            if (!$student->entry_academic_year_id) {
                $student->entry_academic_year_id = $rombel->academic_year_id;
                $student->save();
            }

            return $this->record($tenantId, $actorId, $student, $rombel->academic_year_id, null, $rombel->id,
                'ENROLLED', $effectiveDate, $reason, ['operation' => 'INITIAL_ENROLLMENT']);
        }, 3);
    }

    public function transfer(string $tenantId, string $actorId, string $studentId, string $fromRombelId, string $toRombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $fromRombelId, $toRombelId, $effectiveDate, $reason) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $this->assertStudentEligible($tenantId, $student);
            $from = $this->lockRombel($tenantId, $fromRombelId, false, true);
            $to = $this->lockRombel($tenantId, $toRombelId, true, true);

            if ((string) $from->academic_year_id !== (string) $to->academic_year_id) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Mutasi rombel harus tetap pada tahun pelajaran yang sama. Gunakan Naik Kelas atau Tinggal Kelas untuk tahun berikutnya.']);
            }
            if ((int) $from->gradeLevel->level !== (int) $to->gradeLevel->level) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Mutasi rombel hanya dapat dilakukan pada tingkat kelas yang sama.']);
            }
            if ((string) $from->id === (string) $to->id) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Rombel tujuan sama dengan rombel asal.']);
            }

            $membership = $this->lockMembership($tenantId, $student->id, $from->id, $from->academic_year_id);
            if (!$membership) {
                throw ValidationException::withMessages(['actionFromRombelId' => 'Penempatan rombel asal tidak ditemukan.']);
            }
            $otherPlacement = DB::table('rombel_student')->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)->where('academic_year_id', $to->academic_year_id)
                ->where('rombel_id', '<>', $from->id)->exists();
            if ($otherPlacement) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Siswa sudah memiliki penempatan lain pada tahun pelajaran ini.']);
            }

            DB::table('rombel_student')->where('tenant_id', $tenantId)->where('id', $membership->id)->delete();
            $this->attach($tenantId, $student, $to);

            return $this->record($tenantId, $actorId, $student, $to->academic_year_id, $from->id, $to->id,
                'TRANSFERRED', $effectiveDate, $reason, ['operation' => 'IN_YEAR_TRANSFER']);
        }, 3);
    }

    public function promote(string $tenantId, string $actorId, string $studentId, string $fromRombelId, string $toRombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return $this->moveToNextYear($tenantId, $actorId, $studentId, $fromRombelId, $toRombelId, $effectiveDate, $reason, 'PROMOTED', 1);
    }

    public function retain(string $tenantId, string $actorId, string $studentId, string $fromRombelId, string $toRombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return $this->moveToNextYear($tenantId, $actorId, $studentId, $fromRombelId, $toRombelId, $effectiveDate, $reason, 'RETAINED', 0);
    }

    private function moveToNextYear(string $tenantId, string $actorId, string $studentId, string $fromRombelId, string $toRombelId, string $effectiveDate, string $reason, string $eventType, int $gradeDelta): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $fromRombelId, $toRombelId, $effectiveDate, $reason, $eventType, $gradeDelta) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $this->assertStudentEligible($tenantId, $student);
            $from = $this->lockRombel($tenantId, $fromRombelId, false, false);
            $to = $this->lockRombel($tenantId, $toRombelId, true);

            if ((string) $from->academic_year_id === (string) $to->academic_year_id) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Naik kelas/tinggal kelas harus menggunakan tahun pelajaran yang berbeda.']);
            }
            if ((int) $to->gradeLevel->level !== (int) $from->gradeLevel->level + $gradeDelta) {
                $message = $eventType === 'PROMOTED'
                    ? 'Tingkat rombel tujuan harus satu tingkat di atas rombel asal.'
                    : 'Tingkat rombel tujuan harus sama dengan rombel asal.';
                throw ValidationException::withMessages(['actionToRombelId' => $message]);
            }

            $membership = $this->lockMembership($tenantId, $student->id, $from->id, $from->academic_year_id);
            if (!$membership) {
                throw ValidationException::withMessages(['actionFromRombelId' => 'Penempatan rombel asal tidak ditemukan.']);
            }
            if (DB::table('rombel_student')->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)->where('academic_year_id', $to->academic_year_id)->exists()) {
                throw ValidationException::withMessages(['actionToRombelId' => 'Siswa sudah memiliki penempatan pada tahun pelajaran tujuan.']);
            }

            // Keep the old-year membership as academic history; add exactly one placement in the new year.
            $this->attach($tenantId, $student, $to);

            return $this->record($tenantId, $actorId, $student, $to->academic_year_id, $from->id, $to->id,
                $eventType, $effectiveDate, $reason, ['operation' => 'NEXT_YEAR_PLACEMENT']);
        }, 3);
    }

    public function remove(string $tenantId, string $actorId, string $studentId, string $rombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $rombelId, $effectiveDate, $reason) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $rombel = $this->lockRombel($tenantId, $rombelId, true);
            $membership = $this->lockMembership($tenantId, $student->id, $rombel->id, $rombel->academic_year_id);
            if (!$membership) {
                throw ValidationException::withMessages(['actionFromRombelId' => 'Penempatan rombel tidak ditemukan.']);
            }

            DB::table('rombel_student')->where('tenant_id', $tenantId)->where('id', $membership->id)->delete();

            return $this->record($tenantId, $actorId, $student, $rombel->academic_year_id, $rombel->id, null,
                'REMOVED', $effectiveDate, $reason, ['operation' => 'REMOVAL']);
        }, 3);
    }

    public function graduate(string $tenantId, string $actorId, string $studentId, string $fromRombelId, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $fromRombelId, $effectiveDate, $reason) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $rombel = $this->lockRombel($tenantId, $fromRombelId, true);
            $membership = $this->lockMembership($tenantId, $student->id, $rombel->id, $rombel->academic_year_id);
            if (!$membership) {
                throw ValidationException::withMessages(['actionFromRombelId' => 'Penempatan rombel kelas akhir tidak ditemukan.']);
            }
            if ((int) $rombel->gradeLevel->level !== 6) {
                throw ValidationException::withMessages(['actionFromRombelId' => 'Kelulusan hanya dapat diproses untuk siswa kelas 6.']);
            }
            if ($student->academic_status !== 'ACTIVE') {
                throw ValidationException::withMessages(['student' => 'Hanya siswa berstatus aktif yang dapat diluluskan.']);
            }

            $before = ['academic_status' => $student->academic_status];
            $student->academic_status = 'GRADUATED';
            $student->save();

            $event = $this->record($tenantId, $actorId, $student, $rombel->academic_year_id, $rombel->id, null,
                'GRADUATED', $effectiveDate, $reason, ['operation' => 'GRADUATION']);
            app(AuditService::class)->log('STUDENT_GRADUATED', 'StudentProfile', $student->id, $before, [
                'academic_status' => 'GRADUATED', 'rombel_id' => $rombel->id, 'reason' => $reason,
            ]);

            return $event;
        }, 3);
    }

    public function changeStatus(string $tenantId, string $actorId, string $studentId, string $targetStatus, string $effectiveDate, string $reason): StudentAcademicEvent
    {
        return DB::transaction(function () use ($tenantId, $actorId, $studentId, $targetStatus, $effectiveDate, $reason) {
            $this->lockTenant($tenantId, $actorId);
            $student = $this->lockStudent($tenantId, $studentId);
            $allowed = ['ACTIVE', 'INACTIVE', 'ARCHIVED', 'ALUMNI'];
            if (!in_array($targetStatus, $allowed, true)) {
                throw ValidationException::withMessages(['targetStatus' => 'Status akademik tidak valid.']);
            }
            $beforeStatus = $student->academic_status;
            $validTransition = match ($targetStatus) {
                'INACTIVE' => $beforeStatus === 'ACTIVE',
                'ARCHIVED' => in_array($beforeStatus, ['ACTIVE', 'INACTIVE'], true),
                'ACTIVE' => $beforeStatus === 'INACTIVE',
                'ALUMNI' => $beforeStatus === 'GRADUATED',
                default => false,
            };
            if (!$validTransition) {
                throw ValidationException::withMessages(['targetStatus' => 'Perubahan status akademik tidak diizinkan dari status saat ini.']);
            }

            $student->academic_status = $targetStatus;
            $student->save();
            $eventType = match ($targetStatus) {
                'ACTIVE' => 'REACTIVATED',
                'INACTIVE' => 'STATUS_CHANGED',
                'ARCHIVED' => 'ARCHIVED',
                'ALUMNI' => 'ALUMNI',
            };
            $event = $this->record($tenantId, $actorId, $student, null, null, null,
                $eventType, $effectiveDate, $reason, ['from_status' => $beforeStatus, 'to_status' => $targetStatus]);
            app(AuditService::class)->log('STUDENT_STATUS_CHANGED', 'StudentProfile', $student->id,
                ['academic_status' => $beforeStatus], ['academic_status' => $targetStatus, 'reason' => $reason]);

            return $event;
        }, 3);
    }

    private function lockTenant(string $tenantId, string $actorId): void
    {
        Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')
            ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();

        $actor = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($actorId)
            ->whereNull('deleted_at')->where('is_active', true)->firstOrFail();
        abort_unless(
            $actor->roles()->count() === 1
                && $actor->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $actor->hasPermission('academic.manage'),
            403,
            'Hanya Admin Madrasah aktif dengan satu role dan permission academic.manage yang dapat mengubah status/penempatan siswa.'
        );
    }

    private function lockStudent(string $tenantId, string $studentId): StudentProfile
    {
        return StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereKey($studentId)->lockForUpdate()->firstOrFail();
    }

    private function assertStudentEligible(string $tenantId, StudentProfile $student): void
    {
        if ($student->academic_status !== 'ACTIVE') {
            throw ValidationException::withMessages(['student' => 'Siswa tidak berstatus aktif secara akademik.']);
        }

        $user = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($student->user_id)
            ->whereNull('deleted_at')->where('is_active', true)->lockForUpdate()->firstOrFail();
        if ($user->roles()->count() !== 1 || !$user->roles()->where('name', 'SISWA')->exists()
            || preg_match('/^\\d{10}$/', (string) $student->nisn) !== 1) {
            throw ValidationException::withMessages(['student' => 'Akun Siswa tidak memenuhi role tunggal atau identitas login NISN yang valid.']);
        }
    }

    private function lockRombel(string $tenantId, string $rombelId, bool $requireActiveYear, bool $requireActiveRombel = true): Rombel
    {
        $query = Rombel::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereKey($rombelId);
        if ($requireActiveRombel) $query->where('status', 'ACTIVE');
        $rombel = $query->lockForUpdate()->firstOrFail();
        $rombel->load(['gradeLevel', 'academicYear']);
        if (!$rombel->gradeLevel || !$rombel->academicYear) {
            throw ValidationException::withMessages(['rombel' => 'Rombel tidak memiliki tingkat atau tahun pelajaran yang valid.']);
        }
        if ($requireActiveYear && !$rombel->academicYear->is_active) {
            throw ValidationException::withMessages(['rombel' => 'Rombel tujuan harus berada pada tahun pelajaran aktif.']);
        }

        return $rombel;
    }

    private function lockMembership(string $tenantId, string $studentId, string $rombelId, string $academicYearId): ?object
    {
        return DB::table('rombel_student')->where('tenant_id', $tenantId)
            ->where('student_id', $studentId)->where('rombel_id', $rombelId)
            ->where('academic_year_id', $academicYearId)->lockForUpdate()->first();
    }

    private function attach(string $tenantId, StudentProfile $student, Rombel $rombel): void
    {
        $rombel->students()->attach($student->id, [
            'tenant_id' => $tenantId,
            'academic_year_id' => $rombel->academic_year_id,
        ]);
    }

    private function record(string $tenantId, string $actorId, StudentProfile $student, ?string $academicYearId, ?string $fromRombelId, ?string $toRombelId, string $eventType, string $effectiveDate, string $reason, array $metadata = []): StudentAcademicEvent
    {
        $context = app(TenantContext::class);
        $previousTenantId = $context->getTenantId();
        $context->setTenantId($tenantId);
        try {
            $event = StudentAcademicEvent::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
                'from_rombel_id' => $fromRombelId,
                'to_rombel_id' => $toRombelId,
                'event_type' => $eventType,
                'effective_date' => $effectiveDate,
                'reason' => trim($reason),
                'performed_by' => $actorId,
                'metadata' => $metadata,
            ]);
        } finally {
            $context->setTenantId($previousTenantId);
        }

        app(AuditService::class)->log('STUDENT_ACADEMIC_EVENT', 'StudentAcademicEvent', $event->id, null, [
            'student_id' => $student->id,
            'event_type' => $eventType,
            'academic_year_id' => $academicYearId,
            'from_rombel_id' => $fromRombelId,
            'to_rombel_id' => $toRombelId,
            'effective_date' => $effectiveDate,
            'reason' => trim($reason),
        ]);

        return $event;
    }
}
