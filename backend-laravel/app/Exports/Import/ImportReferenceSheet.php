<?php

namespace App\Exports\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportReferenceSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private readonly array $references) {}

    public function title(): string
    {
        return 'Referensi';
    }

    public function array(): array
    {
        $rows = [['Mata Pelajaran', 'Rombel', 'Tahun Pelajaran', 'Semester']];
        $max = max(1, ...array_map('count', $this->references));

        for ($i = 0; $i < $max; $i++) {
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
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                $workbook = $sheet->getParent();
                $last = max(2, $sheet->getHighestRow());

                foreach ([
                    'SubjectsList' => 'A',
                    'RombelsList' => 'B',
                    'AcademicYearsList' => 'C',
                    'SemestersList' => 'D',
                ] as $name => $column) {
                    $range = '$' . $column . '$2:$' . $column . '$' . $last;
                    $workbook->addNamedRange(new NamedRange($name, $sheet, $range));
                }
            },
        ];
    }
}
