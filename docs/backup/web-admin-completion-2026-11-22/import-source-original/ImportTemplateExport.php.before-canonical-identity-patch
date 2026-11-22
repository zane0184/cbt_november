<?php

namespace App\Exports\Import;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $tenantId,
        private readonly string $type,
    ) {}

    public function sheets(): array
    {
        $references = [
            'subjects' => Subject::query()->where('tenant_id', $this->tenantId)->where('status', 'ACTIVE')
                ->orderBy('code')->get()->map(fn ($x) => $x->code.' — '.$x->name)->values()->all(),
            'rombels' => Rombel::query()->where('tenant_id', $this->tenantId)->where('status', 'ACTIVE')
                ->with('academicYear')->orderBy('name')->get()
                ->map(fn ($x) => $x->name.' — '.$x->academicYear?->name)->values()->all(),
            'years' => AcademicYear::query()->where('tenant_id', $this->tenantId)->whereNull('deleted_at')
                ->orderByDesc('is_active')->orderByDesc('name')->pluck('name')->all(),
            'semesters' => Semester::query()->where('tenant_id', $this->tenantId)->whereNull('deleted_at')
                ->with('academicYear')->orderBy('term')->get()
                ->map(fn ($x) => $x->term.' — '.$x->academicYear?->name)->values()->all(),
        ];

        $sheets = [
            new ImportInstructionSheet($this->type),
            new ImportDataSheet(
                $this->type === 'GURU' ? 'Data Guru' : 'Data Siswa',
                $this->type === 'GURU'
                    ? ['Kategori Kepegawaian*', 'NIP* / kosongkan jika honorer', 'NIK* / kosongkan jika PNS', 'NUPTK (opsional)', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Email (opsional)', 'No. HP (opsional)']
                    : ['NIS (opsional)', 'NISN*', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Nama Orang Tua/Wali', 'No. HP Orang Tua/Wali', 'Alamat', 'Rombel*', 'Tahun Pelajaran*'],
                $this->type === 'GURU'
                    ? [['PNS', '198001012005011001', '', '0012345678901234', 'CONTOH - HAPUS BARIS INI', 'L', '', '']]
                    : [['000123', '0012345678', 'CONTOH - HAPUS BARIS INI', 'P', 'Jakarta', '2015-01-01', 'Nama Wali Contoh', '081234567890', 'Alamat contoh', '', '']],
                $this->type === 'GURU' ? ['B','C','D','H'] : ['A','B','F','H'],
                $this->type === 'SISWA' ? ['J' => $references['rombels'], 'K' => $references['years']] : [],
                $this->type === 'GURU' ? ['A' => ['PNS','HONORER'], 'F' => ['L','P']] : ['D' => ['L','P']],
                ['J' => 2, 'K' => 3],
            ),
        ];

        if ($this->type === 'GURU') {
            $sheets[] = new ImportDataSheet(
                'Penugasan Guru',
                ['Identitas Guru (NIP/NIK)*', 'Mata Pelajaran*', 'Rombel*', 'Tahun Pelajaran*', 'Semester*', 'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Selesai (YYYY-MM-DD)', 'Status (ACTIVE/INACTIVE)'],
                [['CONTOH - HAPUS BARIS INI', '', '', '', '', '', '', 'ACTIVE']],
                ['A','F','G'],
                ['B' => $references['subjects'], 'C' => $references['rombels'], 'D' => $references['years'], 'E' => $references['semesters']],
                ['H' => ['ACTIVE','INACTIVE']],
                ['B' => 1, 'C' => 2, 'D' => 3, 'E' => 4],
            );
        }
        $sheets[] = new ImportReferenceSheet($references);
        return $sheets;
    }
}
