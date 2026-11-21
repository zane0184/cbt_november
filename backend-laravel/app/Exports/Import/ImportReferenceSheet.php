<?php

namespace App\Exports\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportReferenceSheet extends DefaultValueBinder implements FromArray, WithTitle, ShouldAutoSize, WithEvents, WithCustomValueBinder
{
    public function __construct(private readonly array $references) {}

    public function bindValue(Cell $cell, $value): bool
    {
        if ($value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return 'Referensi';
    }

    public function array(): array
    {
        $rows = [['Mata Pelajaran', 'Rombel', 'Tahun Pelajaran', 'Semester']];
        $max = max(array_merge([1], array_values(array_map('count', $this->references))));

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
