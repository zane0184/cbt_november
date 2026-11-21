<?php

namespace App\Exports\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDataSheet extends DefaultValueBinder implements FromArray, WithTitle, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents, WithCustomValueBinder
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headers,
        private readonly array $examples = [],
        private readonly array $textColumns = [],
        private readonly array $referenceColumns = [],
        private readonly array $enumColumns = [],
        private readonly array $referenceColumnIndexes = [],
    ) {}

    public function bindValue(Cell $cell, $value): bool
    {
        $column = $cell->getColumn();
        if ($cell->getRow() > 1 && is_string($value)
            && (in_array($column, $this->textColumns, true) || array_key_exists($column, $this->referenceColumns))) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function title(): string { return $this->sheetTitle; }

    public function array(): array { return array_merge([$this->headers], $this->examples); }

    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->textColumns as $column) $formats[$column] = NumberFormat::FORMAT_TEXT;
        foreach (array_keys($this->referenceColumns) as $column) $formats[$column] = NumberFormat::FORMAT_TEXT;
        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                foreach (array_merge($this->textColumns, array_keys($this->referenceColumns)) as $column) {
                    $sheet->getStyle($column.':'.$column)->getNumberFormat()->setFormatCode('@');
                }
                $lastRow = max(2000, count($this->examples) + 1);
                foreach ($this->referenceColumns as $column => $options) {
                    if (!$options) continue;
                    $index = $this->referenceColumnIndexes[$column] ?? 1;
                    $rangeName = match ($index) { 1 => 'SubjectsList', 2 => 'RombelsList', 3 => 'AcademicYearsList', 4 => 'SemestersList', default => 'SubjectsList' };
                    $formula = '='.$rangeName;
                    $this->applyValidation($sheet, $column, $lastRow, $formula);
                }
                foreach ($this->enumColumns as $column => $options) {
                    $formula = '"'.implode(',', array_map(fn ($x) => str_replace('"', '""', $x), $options)).'"';
                    if (strlen($formula) <= 255) $this->applyValidation($sheet, $column, $lastRow, $formula);
                }
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:'.Coordinate::stringFromColumnIndex(count($this->headers)).'1');
            },
        ];
    }

    private function applyValidation(Worksheet $sheet, string $column, int $lastRow, string $formula): void
    {
        for ($row = 2; $row <= $lastRow; $row++) {
            $validation = $sheet->getCell($column.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Nilai tidak valid');
            $validation->setError('Pilih nilai dari dropdown referensi.');
            $validation->setFormula1($formula);
        }
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1:1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('1:1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF123B35');
        $sheet->getStyle('1:1')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(34);
        return [];
    }
}
