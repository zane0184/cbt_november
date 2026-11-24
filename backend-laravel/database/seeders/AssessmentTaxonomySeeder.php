<?php

namespace Database\Seeders;

use App\Models\AssessmentTaxonomyOption;
use App\Models\CurriculumReferenceSource;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        SystemTenantSeeder::seed();

        DB::transaction(function (): void {
            $tenantId = Tenant::SYSTEM_TENANT_ID;
            $kma = CurriculumReferenceSource::query()
                ->where('tenant_id', $tenantId)
                ->where('document_number', 'KMA 1503 Tahun 2025')
                ->firstOrFail();

            $options = [
                ['FUNCTION', 'FORMATIF', 'Asesmen Formatif', 'KMA 1503/2025 Bab IV, asesmen formatif untuk mengetahui kesiapan belajar serta merefleksi dan memperbaiki proses pembelajaran.', $kma->id, ['is_exhaustive' => false]],
                ['FUNCTION', 'SUMATIF', 'Asesmen Sumatif', 'KMA 1503/2025 Bab IV, asesmen sumatif untuk mengetahui pencapaian hasil belajar dan mengevaluasi efektivitas program pembelajaran.', $kma->id, ['is_exhaustive' => false]],
                ['SCOPE', 'LINGKUP_MATERI', 'Sumatif Lingkup Materi', 'Cakupan asesmen yang dapat dikonfigurasi sesuai tujuan pembelajaran dan kebijakan madrasah.', $kma->id, ['assessment_function' => 'SUMATIF', 'is_exhaustive' => false]],
                ['SCOPE', 'AKHIR_SEMESTER', 'Sumatif Akhir Semester', 'Cakupan akhir semester bersifat pilihan sesuai kebutuhan madrasah.', $kma->id, ['assessment_function' => 'SUMATIF', 'optional' => true, 'is_exhaustive' => false]],
                ['SCOPE', 'AKHIR_TAHUN_AJARAN', 'Sumatif Akhir Tahun Ajaran', 'Cakupan akhir tahun ajaran; bukan nama kegiatan ujian yang dikunci.', $kma->id, ['assessment_function' => 'SUMATIF', 'is_exhaustive' => false]],
                ['SCOPE', 'AKHIR_JENJANG', 'Sumatif Akhir Jenjang', 'Cakupan akhir jenjang; bukan label ujian tetap.', $kma->id, ['assessment_function' => 'SUMATIF', 'is_exhaustive' => false]],
                ['INSTRUMENT', 'OBSERVASI', 'Observasi', 'Teknik asesmen yang disebut pada KMA 1503/2025 Bab IV.', $kma->id, ['is_exhaustive' => false]],
                ['INSTRUMENT', 'PENILAIAN_KINERJA', 'Penilaian Kinerja', 'Teknik asesmen yang disebut pada KMA 1503/2025 Bab IV.', $kma->id, ['is_exhaustive' => false]],
                ['INSTRUMENT', 'TES_TERTULIS', 'Tes Tertulis', 'Teknik asesmen yang disebut pada KMA 1503/2025 Bab IV.', $kma->id, ['is_exhaustive' => false]],
                ['INSTRUMENT', 'TES_LISAN', 'Tes Lisan', 'Teknik asesmen yang disebut pada KMA 1503/2025 Bab IV.', $kma->id, ['is_exhaustive' => false]],
                ['INSTRUMENT', 'PORTOFOLIO', 'Portofolio', 'Teknik asesmen yang disebut pada KMA 1503/2025 Bab IV.', $kma->id, ['is_exhaustive' => false]],
                ['INSTRUMENT', 'BENTUK_LAIN', 'Bentuk Lain Sesuai Tujuan', 'KMA 1503/2025 Bab IV memperbolehkan bentuk lain yang sesuai dengan tujuan penilaian.', $kma->id, ['is_exhaustive' => false]],
                ['DELIVERY_MODE', 'CBT', 'CBT', 'Mode pelaksanaan terkomputerisasi yang didukung aplikasi.', null, ['system_defined' => true]],
                ['DELIVERY_MODE', 'NON_CBT', 'Non-CBT', 'Mode asesmen di luar pelaksanaan terkomputerisasi.', null, ['system_defined' => true]],
            ];

            foreach ($options as $index => [$dimension, $code, $label, $description, $sourceId, $metadata]) {
                AssessmentTaxonomyOption::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'dimension' => $dimension,
                        'code' => $code,
                        'version_code' => 'KMA-1503-2025',
                    ],
                    [
                        'label' => $label,
                        'description' => $description,
                        'source_id' => $sourceId,
                        'effective_from' => '2025-09-22',
                        'status' => 'ACTIVE',
                        'is_selectable' => true,
                        'is_official' => true,
                        'is_editable' => false,
                        'sort_order' => $index + 1,
                        'metadata' => $metadata + ['customizable' => true],
                    ]
                );
            }
        });
    }
}
