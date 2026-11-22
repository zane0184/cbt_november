<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumVersion;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CurriculumRegistrationService
{
    public function createTenantCurriculum(string $tenantId, string $actorId, string $code, string $name, ?string $description, string $reason): MultiCurriculum
    {
        return DB::transaction(function () use ($tenantId, $actorId, $code, $name, $description, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $this->validateReason($reason);

            $code = strtoupper(trim($code));
            $name = trim($name);
            Validator::make(
                ['code' => $code, 'name' => $name, 'description' => $description],
                [
                    'code' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]+$/'],
                    'name' => ['required', 'string', 'min:3', 'max:160'],
                    'description' => ['nullable', 'string', 'max:4000'],
                ]
            )->validate();

            $exists = MultiCurriculum::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('code', $code)->whereNull('deleted_at')->exists();
            if ($exists) {
                throw ValidationException::withMessages(['curriculumCode' => 'Kode kurikulum sudah digunakan pada madrasah ini.']);
            }

            $curriculum = MultiCurriculum::create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => $name,
                'description' => $description ? trim($description) : null,
                'status' => 'ACTIVE',
            ]);

            app(AuditService::class)->log('CURRICULUM_CREATED', 'MultiCurriculum', $curriculum->id, null, [
                'code' => $code, 'name' => $name, 'reason' => trim($reason),
            ]);

            return $curriculum;
        }, 3);
    }

    public function createTenantVersion(string $tenantId, string $actorId, string $curriculumId, string $versionCode, string $title, ?int $effectiveYear, string $reason): MultiCurriculumVersion
    {
        return DB::transaction(function () use ($tenantId, $actorId, $curriculumId, $versionCode, $title, $effectiveYear, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $this->validateReason($reason);

            $curriculum = MultiCurriculum::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->whereKey($curriculumId)->lockForUpdate()->firstOrFail();
            if ($curriculum->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['selectedCurriculumId' => 'Kurikulum nonaktif tidak dapat diberi versi baru.']);
            }

            $versionCode = strtoupper(trim($versionCode));
            $title = trim($title);
            Validator::make(
                ['versionCode' => $versionCode, 'title' => $title, 'effectiveYear' => $effectiveYear],
                [
                    'versionCode' => ['required', 'string', 'min:1', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
                    'title' => ['required', 'string', 'min:3', 'max:160'],
                    'effectiveYear' => ['nullable', 'integer', 'between:2020,2100'],
                ]
            )->validate();

            if (MultiCurriculumVersion::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_id', $curriculum->id)->where('version_code', $versionCode)
                ->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['versionCode' => 'Kode versi sudah digunakan pada kurikulum ini.']);
            }

            $version = MultiCurriculumVersion::create([
                'tenant_id' => $tenantId,
                'multi_curriculum_id' => $curriculum->id,
                'version_code' => $versionCode,
                'title' => $title,
                'effective_year' => $effectiveYear,
                'status' => 'ACTIVE',
            ]);

            app(AuditService::class)->log('CURRICULUM_VERSION_CREATED', 'MultiCurriculumVersion', $version->id, null, [
                'curriculum_id' => $curriculum->id,
                'version_code' => $versionCode,
                'effective_year' => $effectiveYear,
                'reason' => trim($reason),
            ]);

            return $version;
        }, 3);
    }

    public function registerVersion(string $tenantId, string $actorId, string $academicYearId, string $versionId, string $reason): TenantCurriculumRegistration
    {
        return DB::transaction(function () use ($tenantId, $actorId, $academicYearId, $versionId, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $this->validateReason($reason);

            $year = AcademicYear::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->whereKey($academicYearId)->lockForUpdate()->firstOrFail();
            $version = MultiCurriculumVersion::withoutGlobalScopes()->whereKey($versionId)
                ->whereNull('deleted_at')->lockForUpdate()->firstOrFail();

            $ownerId = (string) $version->tenant_id;
            if ($ownerId !== Tenant::SYSTEM_TENANT_ID && $ownerId !== $tenantId) {
                abort(403, 'Kurikulum ini bukan katalog sistem atau milik madrasah yang sedang aktif.');
            }

            $curriculum = MultiCurriculum::withoutGlobalScopes()->where('tenant_id', $ownerId)
                ->whereNull('deleted_at')->whereKey($version->multi_curriculum_id)->lockForUpdate()->firstOrFail();

            if ($curriculum->status !== 'ACTIVE' || $version->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['selectedVersionId' => 'Hanya kurikulum dan versi aktif yang dapat diregistrasikan.']);
            }

            $yearStart = (int) substr((string) $year->name, 0, 4);
            if ($version->effective_year !== null && (int) $version->effective_year !== $yearStart) {
                throw ValidationException::withMessages(['selectedVersionId' => 'Tahun efektif versi tidak sesuai dengan tahun pelajaran yang dipilih.']);
            }

            $existing = TenantCurriculumRegistration::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('academic_year_id', $year->id)
                ->where('status', 'ACTIVE')
                ->whereHas('version', fn ($query) => $query->where('multi_curriculum_id', $curriculum->id))
                ->lockForUpdate()->get();

            foreach ($existing as $previous) {
                if ((string) $previous->multi_curriculum_version_id === (string) $version->id) {
                    throw ValidationException::withMessages(['selectedVersionId' => 'Versi ini sudah aktif terdaftar pada tahun pelajaran tersebut.']);
                }

                $before = ['status' => $previous->status, 'version_id' => $previous->multi_curriculum_version_id];
                $previous->status = 'INACTIVE';
                $previous->save();

                app(AuditService::class)->log('CURRICULUM_REGISTRATION_REPLACED', 'TenantCurriculumRegistration', $previous->id, $before, [
                    'status' => 'INACTIVE',
                    'replacement_version_id' => $version->id,
                    'academic_year_id' => $year->id,
                    'reason' => trim($reason),
                ]);
            }

            $context = app(TenantContext::class);
            $previousTenantId = $context->getTenantId();
            $context->setTenantId($tenantId);
            try {
                $registration = TenantCurriculumRegistration::create([
                    'tenant_id' => $tenantId,
                    'academic_year_id' => $year->id,
                    'multi_curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $ownerId,
                    'status' => 'ACTIVE',
                    'registered_at' => now(),
                ]);
            } finally {
                $context->setTenantId($previousTenantId);
            }

            app(AuditService::class)->log('CURRICULUM_REGISTERED', 'TenantCurriculumRegistration', $registration->id, null, [
                'academic_year_id' => $year->id,
                'academic_year' => $year->name,
                'curriculum_id' => $curriculum->id,
                'curriculum_name' => $curriculum->name,
                'version_id' => $version->id,
                'version_code' => $version->version_code,
                'curriculum_owner_id' => $ownerId,
                'reason' => trim($reason),
            ]);

            return $registration;
        }, 3);
    }

    public function deactivateRegistration(string $tenantId, string $actorId, string $registrationId, string $reason): TenantCurriculumRegistration
    {
        return DB::transaction(function () use ($tenantId, $actorId, $registrationId, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $this->validateReason($reason);

            $registration = TenantCurriculumRegistration::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)->whereKey($registrationId)->lockForUpdate()->firstOrFail();

            if ($registration->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['registration' => 'Registrasi ini sudah tidak aktif.']);
            }

            $before = ['status' => $registration->status];
            $registration->status = 'INACTIVE';
            $registration->save();

            app(AuditService::class)->log('CURRICULUM_REGISTRATION_DEACTIVATED', 'TenantCurriculumRegistration', $registration->id, $before, [
                'status' => 'INACTIVE',
                'academic_year_id' => $registration->academic_year_id,
                'version_id' => $registration->multi_curriculum_version_id,
                'reason' => trim($reason),
            ]);

            return $registration;
        }, 3);
    }

    private function lockTenantAndActor(string $tenantId, string $actorId): void
    {
        Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')
            ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();

        abort_unless((string) auth()->id() === $actorId, 403, 'Aktor audit tidak sesuai dengan sesi Admin.');
        $actor = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($actorId)
            ->whereNull('deleted_at')->where('is_active', true)->firstOrFail();

        abort_unless(
            $actor->roles()->count() === 1
                && $actor->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $actor->hasPermission('curriculum.manage'),
            403,
            'Hanya Admin Madrasah aktif dengan satu role dan permission curriculum.manage yang dapat mengelola kurikulum.'
        );
    }

    private function validateReason(string $reason): void
    {
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ])->validate();
    }
}
