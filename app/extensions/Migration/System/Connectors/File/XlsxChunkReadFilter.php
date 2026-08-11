<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class XlsxChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow = 2,
        private int $endRow = 1001,
        private readonly int $headerRow = 1,
    ) {
    }

    public function setRows(int $startRow, int $endRow): void
    {
        $this->startRow = max(1, $startRow);
        $this->endRow = max($this->startRow, $endRow);
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row === $this->headerRow || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
