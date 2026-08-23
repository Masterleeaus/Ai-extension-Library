<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;

final class XlsxConnector extends AbstractFileConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'xlsx',
            name: 'Excel XLSX File',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'schema_discovery', 'field_profiling', 'chunked_stream', 'sheet_selection', 'checkpoint_offset'],
            supportsDiscovery: true,
            readOnly: true,
            streamingMode: 'chunked',
        );
    }

    protected function rows(array $configuration): iterable
    {
        if (! class_exists(Xlsx::class)) {
            throw new RuntimeException('PhpSpreadsheet XLSX reader is required for XLSX migration sources.');
        }

        $path = $this->path($configuration);
        $chunkSize = max(50, min(5000, (int) ($configuration['chunk_size'] ?? 500)));
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $worksheetInfo = $reader->listWorksheetInfo($path);
        if ($worksheetInfo === []) {
            return;
        }

        $requestedSheet = isset($configuration['sheet']) ? (string) $configuration['sheet'] : null;
        $sheetInfo = $worksheetInfo[0];
        if ($requestedSheet !== null && $requestedSheet !== '') {
            foreach ($worksheetInfo as $candidate) {
                if (($candidate['worksheetName'] ?? null) === $requestedSheet) {
                    $sheetInfo = $candidate;
                    break;
                }
            }
        }

        $sheetName = (string) ($sheetInfo['worksheetName'] ?? 'Worksheet');
        $totalRows = (int) ($sheetInfo['totalRows'] ?? 0);
        $totalColumns = max(1, (int) ($sheetInfo['totalColumns'] ?? 1));
        $filter = new XlsxChunkReadFilter();
        $reader->setReadFilter($filter);
        $reader->setLoadSheetsOnly([$sheetName]);
        $headers = null;

        for ($start = 2; $start <= max(2, $totalRows); $start += $chunkSize) {
            $end = min($totalRows, $start + $chunkSize - 1);
            $filter->setRows($start, max($start, $end));
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

            if ($headers === null) {
                $headers = [];
                for ($column = 1; $column <= $totalColumns; $column++) {
                    $value = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . '1')->getValue();
                    $name = trim((string) $value);
                    $headers[] = $name !== '' ? $name : 'column_' . $column;
                }
                $headers = $this->deduplicateHeaders($headers);
            }

            if ($totalRows >= 2) {
                for ($row = $start; $row <= $end; $row++) {
                    $record = [];
                    $hasValue = false;
                    foreach ($headers as $index => $header) {
                        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($index + 1) . $row);
                        $value = $cell->getCalculatedValue();
                        $record[$header] = $value;
                        $hasValue = $hasValue || $value !== null && $value !== '';
                    }
                    if ($hasValue) {
                        yield $record;
                    }
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
        }
    }

    /** @param array<int, string> $headers
     *  @return array<int, string>
     */
    private function deduplicateHeaders(array $headers): array
    {
        $seen = [];
        foreach ($headers as $index => $header) {
            $base = $header;
            $candidate = $base;
            $suffix = 2;
            while (isset($seen[$candidate])) {
                $candidate = $base . '_' . $suffix++;
            }
            $seen[$candidate] = true;
            $headers[$index] = $candidate;
        }

        return $headers;
    }
}
