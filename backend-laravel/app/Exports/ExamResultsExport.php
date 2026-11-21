<?php

namespace App\Exports;

use App\Models\Exam;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExamResultsExport implements FromCollection, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        protected Exam $exam,
        protected \Illuminate\Support\Collection $results
    ) {}

    public function collection()
    {
        return $this->results;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Siswa',
            'Kelas/Rombel',
            'Exam',
            'Mapel',
            'Score',
            'Max Score',
            'Percentage',
            'Status',
            'Submitted At',
            'Finalized At',
        ];
    }

    public function map($attempt): array
    {
        $this->rowNumber++;

        $rombel = $attempt->participant->user->studentProfile?->rombels->first()?->name ?? '-';
        $subject = $attempt->exam->assessment->subject?->name ?? '-';

        return [
            $this->rowNumber,
            $attempt->participant->user->name,
            $rombel,
            $attempt->exam->title,
            $subject,
            $attempt->score ? $attempt->score->raw_score : 0,
            $attempt->score ? $attempt->score->max_score : 0,
            $attempt->score ? $attempt->score->percentage : 0,
            $attempt->status,
            $attempt->submitted_at?->toDateTimeString(),
            $attempt->score?->finalized_at?->toDateTimeString(),
        ];
    }
}
