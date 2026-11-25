<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Phase;
use App\Models\Semester;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TenantAcademicBootstrapper
{
    /**
     * Provision the minimum tenant-local academic catalog required for a first
     * operational pilot. CP/TP content is deliberately not fabricated here.
     */
    public function bootstrap(string $tenantId): void
    {
        if ((string) app(TenantContext::class)->getTenantId() !== $tenantId) {
            throw new RuntimeException('Tenant context mismatch during academic bootstrap.');
        }

        DB::transaction(function () use ($tenantId): void {
            $version = MultiCurriculumVersion::query()
                ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                ->where('version_code', 'KMA-1503-2025')
                ->where('status', 'ACTIVE')
                ->first();

            if (!$version || !$version->is_official || $version->is_editable) {
                throw new RuntimeException('Verified KMA 1503/2025 phase registry is unavailable.');
            }

            $phaseNodes = MultiCurriculumStructure::query()
                ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'PHASE')
                ->where('status', 'ACTIVE')
                ->get()
                ->keyBy('code');

            $gradeNodes = MultiCurriculumStructure::query()
                ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'GRADE_LEVEL')
                ->where('status', 'ACTIVE')
                ->get();

            $expectedPhases = ['FASE_A', 'FASE_B', 'FASE_C'];
            $expectedGrades = ['MI_1', 'MI_2', 'MI_3', 'MI_4', 'MI_5', 'MI_6'];
            if ($phaseNodes->keys()->sort()->values()->all() !== $expectedPhases) {
                throw new RuntimeException('KMA 1503/2025 phase registry is incomplete or ambiguous.');
            }

            $gradeCodes = $gradeNodes->pluck('code')->sort()->values()->all();
            if ($gradeCodes !== $expectedGrades) {
                throw new RuntimeException('KMA 1503/2025 MI grade registry is incomplete or ambiguous.');
            }

            $curriculum = Curriculum::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('code', 'KURIKULUM_MERDEKA')
                ->lockForUpdate()
                ->first();

            if (!$curriculum) {
                $curriculum = Curriculum::create([
                    'name' => 'Kurikulum Merdeka',
                    'code' => 'KURIKULUM_MERDEKA',
                    'description' => 'Katalog fase dan tingkat kelas MI diturunkan dari registry KMA 1503/2025. Konten CP resmi tidak dibuat otomatis.',
                    'status' => 'ACTIVE',
                    'is_active' => true,
                ]);
            } elseif (
                $curriculum->name !== 'Kurikulum Merdeka'
                || $curriculum->status !== 'ACTIVE'
                || !$curriculum->is_active
            ) {
                throw new RuntimeException('Existing tenant curriculum conflicts with the required bootstrap identity.');
            }

            $localPhases = [];
            foreach ($expectedPhases as $phaseCode) {
                $source = $phaseNodes->get($phaseCode);
                $phase = Phase::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('curriculum_id', $curriculum->id)
                    ->where('code', $phaseCode)
                    ->lockForUpdate()
                    ->first();

                if (!$phase) {
                    $phase = Phase::create([
                        'curriculum_id' => $curriculum->id,
                        'name' => $source->title,
                        'code' => $phaseCode,
                        'description' => $source->source_locator,
                        'status' => 'ACTIVE',
                    ]);
                } elseif (
                    $phase->name !== $source->title
                    || $phase->status !== 'ACTIVE'
                ) {
                    throw new RuntimeException("Existing phase {$phaseCode} conflicts with the official phase registry.");
                }

                $localPhases[$phaseCode] = $phase;
            }

            foreach ($gradeNodes as $source) {
                $parent = $phaseNodes->firstWhere('id', $source->parent_id);
                if (!$parent || !isset($localPhases[$parent->code])) {
                    throw new RuntimeException("MI grade {$source->code} has no valid phase parent.");
                }

                $gradeNumber = (int) ($source->metadata['grade_number'] ?? 0);
                if ($gradeNumber < 1 || $gradeNumber > 6) {
                    throw new RuntimeException("MI grade {$source->code} has invalid grade metadata.");
                }

                $grade = GradeLevel::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('phase_id', $localPhases[$parent->code]->id)
                    ->where('code', $source->code)
                    ->lockForUpdate()
                    ->first();

                if (!$grade) {
                    GradeLevel::create([
                        'phase_id' => $localPhases[$parent->code]->id,
                        'name' => $source->title,
                        'code' => $source->code,
                        'level' => $gradeNumber,
                        'status' => 'ACTIVE',
                    ]);
                } elseif (
                    $grade->name !== $source->title
                    || (int) $grade->level !== $gradeNumber
                    || $grade->status !== 'ACTIVE'
                ) {
                    throw new RuntimeException("Existing grade {$source->code} conflicts with the official grade registry.");
                }
            }

            $month = (int) now(config('app.timezone', 'Asia/Jakarta'))->format('n');
            $calendarYear = (int) now(config('app.timezone', 'Asia/Jakarta'))->format('Y');
            $startYear = $month >= 7 ? $calendarYear : $calendarYear - 1;
            $academicYearName = $startYear . '/' . ($startYear + 1);
            $activeTerm = $month >= 7 ? 'GANJIL' : 'GENAP';

            $academicYear = AcademicYear::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('name', $academicYearName)
                ->lockForUpdate()
                ->first();

            if (!$academicYear) {
                $academicYear = AcademicYear::create([
                    'name' => $academicYearName,
                    'is_active' => true,
                ]);
            } elseif (!$academicYear->is_active) {
                throw new RuntimeException('Existing current academic year is inactive; installer will not change it.');
            }

            foreach (['GANJIL', 'GENAP'] as $term) {
                $semester = Semester::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('term', $term)
                    ->lockForUpdate()
                    ->first();

                if (!$semester) {
                    Semester::create([
                        'academic_year_id' => $academicYear->id,
                        'term' => $term,
                        'is_active' => $term === $activeTerm,
                    ]);
                } elseif ((bool) $semester->is_active !== ($term === $activeTerm)) {
                    throw new RuntimeException("Existing semester {$term} conflicts with the current academic period.");
                }
            }
        });
    }
}
