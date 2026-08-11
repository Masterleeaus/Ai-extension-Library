<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors;

use App\Extensions\Migration\System\Connectors\Api\GraphqlConnector;
use App\Extensions\Migration\System\Connectors\Api\OpenApiConnector;
use App\Extensions\Migration\System\Connectors\Api\RestConnector;
use App\Extensions\Migration\System\Connectors\Database\MySqlConnector;
use App\Extensions\Migration\System\Connectors\Database\PostgreSqlConnector;
use App\Extensions\Migration\System\Connectors\Database\SqliteConnector;
use App\Extensions\Migration\System\Connectors\Database\SqlServerConnector;
use App\Extensions\Migration\System\Connectors\File\CsvConnector;
use App\Extensions\Migration\System\Connectors\File\JsonConnector;
use App\Extensions\Migration\System\Connectors\File\JsonLinesConnector;
use App\Extensions\Migration\System\Connectors\File\SqlDumpConnector;
use App\Extensions\Migration\System\Connectors\File\XlsxConnector;
use App\Extensions\Migration\System\Connectors\File\XmlConnector;
use App\Extensions\Migration\System\Connectors\File\ZipBundleConnector;

final class BuiltinConnectorCatalog
{
    /** @return array<int, class-string> */
    public function classes(): array
    {
        return [
            CsvConnector::class,
            XlsxConnector::class,
            JsonConnector::class,
            JsonLinesConnector::class,
            XmlConnector::class,
            ZipBundleConnector::class,
            SqlDumpConnector::class,
            MySqlConnector::class,
            PostgreSqlConnector::class,
            SqlServerConnector::class,
            SqliteConnector::class,
            RestConnector::class,
            GraphqlConnector::class,
            OpenApiConnector::class,
        ];
    }
}
