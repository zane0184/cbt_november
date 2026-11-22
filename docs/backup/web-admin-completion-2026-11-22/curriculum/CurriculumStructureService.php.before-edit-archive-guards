<?php

namespace App\Services;

use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CurriculumStructureService
{
    public function createNode(string $tenantId, string $actorId, string $versionId, ?string $parentId, string $nodeType, string $code, string $title, int $sortOrder, string $reason): MultiCurriculumStructure
    {
        return DB::transaction(function () use ($tenantId, $actorId, $versionId, $parentId, $nodeType, $code, $title, $sortOrder, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $version = $this->lockEditableVersion($tenantId, $versionId);
            $this->validateReason($reason);

            $nodeType = strtoupper(trim($nodeType));
            $code = strtoupper(trim($code));
            $title = trim($title);
            Validator::make(
                ['nodeType' => $nodeType, 'code' => $code, 'title' => $title, 'sortOrder' => $sortOrder],
                [
                    'nodeType' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Z][A-Z0-9_]+$/'],
                    'code' => ['required', 'string', 'min:1', 'max:60', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
                    'title' => ['required', 'string', 'min:2', 'max:200'],
                    'sortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
                ]
            )->validate();

            $parent = $parentId
                ? MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('multi_curriculum_version_id', $version->id)->whereNull('deleted_at')
                    ->whereKey($parentId)->lockForUpdate()->firstOrFail()
                : null;

            if (MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $version->id)->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['nodeCode' => 'Kode struktur sudah digunakan pada versi kurikulum ini.']);
            }

            $node = MultiCurriculumStructure::create([
                'tenant_id' => $tenantId,
                'multi_curriculum_version_id' => $version->id,
                'parent_id' => $parent?->id,
                'node_type' => $nodeType,
                'code' => $code,
                'title' => $title,
                'sort_order' => $sortOrder,
                'status' => 'ACTIVE',
            ]);

            app(AuditService::class)->log('CURRICULUM_STRUCTURE_CREATED', 'MultiCurriculumStructure', $node->id, null, [
                'version_id' => $version->id, 'parent_id' => $parent?->id,
                'node_type' => $nodeType, 'code' => $code, 'title' => $title,
                'reason' => trim($reason),
            ]);

            return $node;
        }, 3);
    }

    public function updateNode(string $tenantId, string $actorId, string $nodeId, ?string $parentId, string $nodeType, string $code, string $title, int $sortOrder, string $reason): MultiCurriculumStructure
    {
        return DB::transaction(function () use ($tenantId, $actorId, $nodeId, $parentId, $nodeType, $code, $title, $sortOrder, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $node = MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->whereKey($nodeId)->lockForUpdate()->firstOrFail();
            $version = $this->lockEditableVersion($tenantId, $node->multi_curriculum_version_id);
            $this->validateReason($reason);

            $nodeType = strtoupper(trim($nodeType));
            $code = strtoupper(trim($code));
            $title = trim($title);
            Validator::make(
                ['nodeType' => $nodeType, 'code' => $code, 'title' => $title, 'sortOrder' => $sortOrder],
                [
                    'nodeType' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Z][A-Z0-9_]+$/'],
                    'code' => ['required', 'string', 'min:1', 'max:60', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
                    'title' => ['required', 'string', 'min:2', 'max:200'],
                    'sortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
                ]
            )->validate();

            $parent = $parentId
                ? MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('multi_curriculum_version_id', $version->id)->whereNull('deleted_at')
                    ->whereKey($parentId)->lockForUpdate()->firstOrFail()
                : null;

            $cursor = $parent;
            while ($cursor) {
                if ((string) $cursor->id === (string) $node->id) {
                    throw ValidationException::withMessages(['parentId' => 'Struktur tidak boleh menjadi anak dari dirinya sendiri atau turunannya.']);
                }
                $cursor = $cursor->parent_id
                    ? MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                        ->where('multi_curriculum_version_id', $version->id)->whereNull('deleted_at')
                        ->whereKey($cursor->parent_id)->first()
                    : null;
            }

            if (MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $version->id)->where('code', $code)
                ->where('id', '<>', $node->id)->exists()) {
                throw ValidationException::withMessages(['nodeCode' => 'Kode struktur sudah digunakan pada versi kurikulum ini.']);
            }

            $before = $node->only(['parent_id', 'node_type', 'code', 'title', 'sort_order', 'status']);
            $node->fill([
                'parent_id' => $parent?->id,
                'node_type' => $nodeType,
                'code' => $code,
                'title' => $title,
                'sort_order' => $sortOrder,
            ]);
            $node->save();

            app(AuditService::class)->log('CURRICULUM_STRUCTURE_UPDATED', 'MultiCurriculumStructure', $node->id, $before, [
                ...$node->only(['parent_id', 'node_type', 'code', 'title', 'sort_order', 'status']),
                'reason' => trim($reason),
            ]);

            return $node;
        }, 3);
    }

    public function archiveNode(string $tenantId, string $actorId, string $nodeId, string $reason): MultiCurriculumStructure
    {
        return DB::transaction(function () use ($tenantId, $actorId, $nodeId, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $node = MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->whereKey($nodeId)->lockForUpdate()->firstOrFail();
            $this->lockEditableVersion($tenantId, $node->multi_curriculum_version_id);
            $this->validateReason($reason);

            $hasActiveChildren = MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $node->multi_curriculum_version_id)
                ->where('parent_id', $node->id)->where('status', 'ACTIVE')->whereNull('deleted_at')->exists();
            if ($hasActiveChildren) {
                throw ValidationException::withMessages(['node' => 'Arsipkan anak struktur aktif terlebih dahulu.']);
            }

            $before = ['status' => $node->status];
            $node->status = 'ARCHIVED';
            $node->save();
            app(AuditService::class)->log('CURRICULUM_STRUCTURE_ARCHIVED', 'MultiCurriculumStructure', $node->id, $before, [
                'status' => 'ARCHIVED', 'reason' => trim($reason),
            ]);

            return $node;
        }, 3);
    }

    public function createOutcome(string $tenantId, string $actorId, string $versionId, string $structureId, string $code, string $outcomeType, string $description, string $reason): MultiLearningOutcome
    {
        return DB::transaction(function () use ($tenantId, $actorId, $versionId, $structureId, $code, $outcomeType, $description, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $version = $this->lockEditableVersion($tenantId, $versionId);
            $this->validateReason($reason);

            $structure = MultiCurriculumStructure::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $version->id)->where('status', 'ACTIVE')
                ->whereNull('deleted_at')->whereKey($structureId)->lockForUpdate()->firstOrFail();

            $code = strtoupper(trim($code));
            $outcomeType = strtoupper(trim($outcomeType));
            $description = trim($description);
            Validator::make(
                ['code' => $code, 'outcomeType' => $outcomeType, 'description' => $description],
                [
                    'code' => ['required', 'string', 'min:1', 'max:60', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
                    'outcomeType' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Z][A-Z0-9_]+$/'],
                    'description' => ['required', 'string', 'min:10', 'max:10000'],
                ]
            )->validate();

            if (MultiLearningOutcome::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('multi_curriculum_version_id', $version->id)->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['outcomeCode' => 'Kode capaian sudah digunakan pada versi kurikulum ini.']);
            }

            $outcome = MultiLearningOutcome::create([
                'tenant_id' => $tenantId,
                'multi_curriculum_structure_id' => $structure->id,
                'multi_curriculum_version_id' => $version->id,
                'code' => $code,
                'outcome_type' => $outcomeType,
                'description' => $description,
                'status' => 'ACTIVE',
            ]);

            app(AuditService::class)->log('CURRICULUM_OUTCOME_CREATED', 'MultiLearningOutcome', $outcome->id, null, [
                'version_id' => $version->id, 'structure_id' => $structure->id,
                'code' => $code, 'outcome_type' => $outcomeType,
                'reason' => trim($reason),
            ]);

            return $outcome;
        }, 3);
    }

    public function archiveOutcome(string $tenantId, string $actorId, string $outcomeId, string $reason): MultiLearningOutcome
    {
        return DB::transaction(function () use ($tenantId, $actorId, $outcomeId, $reason) {
            $this->lockTenantAndActor($tenantId, $actorId);
            $outcome = MultiLearningOutcome::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->whereKey($outcomeId)->lockForUpdate()->firstOrFail();
            $this->lockEditableVersion($tenantId, $outcome->multi_curriculum_version_id);
            $this->validateReason($reason);

            $before = ['status' => $outcome->status];
            $outcome->status = 'ARCHIVED';
            $outcome->save();
            app(AuditService::class)->log('CURRICULUM_OUTCOME_ARCHIVED', 'MultiLearningOutcome', $outcome->id, $before, [
                'status' => 'ARCHIVED', 'reason' => trim($reason),
            ]);

            return $outcome;
        }, 3);
    }

    private function lockEditableVersion(string $tenantId, string $versionId): MultiCurriculumVersion
    {
        $version = MultiCurriculumVersion::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereKey($versionId)->lockForUpdate()->firstOrFail();

        $curriculum = MultiCurriculum::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereKey($version->multi_curriculum_id)->firstOrFail();

        if ($curriculum->status !== 'ACTIVE' || $version->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['version' => 'Kurikulum atau versi sudah tidak aktif.']);
        }

        $isRegistered = TenantCurriculumRegistration::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('multi_curriculum_version_id', $version->id)->exists();
        if ($isRegistered) {
            throw ValidationException::withMessages(['version' => 'Versi yang pernah diregistrasikan bersifat terkunci. Buat versi baru untuk perubahan berikutnya.']);
        }

        return $version;
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
            'Hanya Admin Madrasah aktif dengan satu role dan permission curriculum.manage yang dapat mengelola struktur kurikulum.'
        );
    }

    private function validateReason(string $reason): void
    {
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ])->validate();
    }
}
