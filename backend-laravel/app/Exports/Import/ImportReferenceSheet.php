<?php

namespace App\Exports\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportReferenceSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private readonly array $references) {}
    public function title(): string { return 'Referensi'; }

    public function array(): array
    {
        $columns = ['Mata Pelajaran','Rombel','Tahun Pelajaran','Semester'];
        $rows = [$columns];
        $max = max(1, ...array_map('count', $this->references));
        for ($i=0; $i<$max; $i++) {
            $rows[] = [
                $this->references['subjects'][$i] ?? null,
                $this->references['rombels'][$i] ?? null,
                $this->references['years'][$i] ?? null,
                $this->references['semesters'][$i] ?? null,
            ];
        }
        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        }];
    }
}
