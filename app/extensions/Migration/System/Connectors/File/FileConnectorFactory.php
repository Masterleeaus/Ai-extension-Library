<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use InvalidArgumentException;

final class FileConnectorFactory
{
    public function forPath(string $path): MigrationSourceConnectorInterface
    {
        return $this->forExtension(strtolower(pathinfo($path, PATHINFO_EXTENSION)));
    }

    public function forExtension(string $extension): MigrationSourceConnectorInterface
    {
        return match (strtolower(ltrim($extension, '.'))) {
            'csv' => new CsvConnector(),
            'xlsx' => new XlsxConnector(),
            'json' => new JsonConnector(),
            'jsonl', 'ndjson' => new JsonLinesConnector(),
            'xml' => new XmlConnector(),
            'sql' => new SqlDumpConnector(),
            default => throw new InvalidArgumentException("Unsupported migration file extension: {$extension}"),
        };
    }

    public function supportsExtension(string $extension): bool
    {
        return in_array(strtolower(ltrim($extension, '.')), ['csv', 'xlsx', 'json', 'jsonl', 'ndjson', 'xml', 'sql'], true);
    }
}
