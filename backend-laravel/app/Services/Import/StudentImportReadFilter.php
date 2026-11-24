<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class StudentImportReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $maxRow,
        private readonly int $maxColumn,
    ) {
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if (!is_string($columnAddress) || !is_numeric($row)) {
            return false;
        }

        $row = (int) $row;
        if ($row < 1 || $row > $this->maxRow) {
            return false;
        }

        try {
            return Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
        } catch (\Throwable) {
            return false;
        }
    }
}
