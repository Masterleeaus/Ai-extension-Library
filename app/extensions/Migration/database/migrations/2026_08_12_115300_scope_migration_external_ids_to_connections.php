<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'ext_migration_external_ids';
    private const OLD_UNIQUE = 'mig_external_ids_source_uq';
    private const NEW_UNIQUE = 'mig_external_ids_source_connection_uq';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::hasColumn(self::TABLE, 'connection_id')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unsignedBigInteger('connection_id')->nullable()->after('project_id');
                $table->index(['company_id', 'connection_id'], 'mig_external_ids_connection_ix');
            });
        }

        if ($this->indexExists(self::OLD_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::OLD_UNIQUE);
            });
        }

        if (! $this->indexExists(self::NEW_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(
                    ['company_id', 'project_id', 'connection_id', 'source_type', 'source_id'],
                    self::NEW_UNIQUE,
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->indexExists(self::NEW_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::NEW_UNIQUE);
            });
        }

        if (! $this->indexExists(self::OLD_UNIQUE)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(
                    ['company_id', 'project_id', 'source_type', 'source_id'],
                    self::OLD_UNIQUE,
                );
            });
        }

        if (Schema::hasColumn(self::TABLE, 'connection_id')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                if ($this->indexExists('mig_external_ids_connection_ix')) {
                    $table->dropIndex('mig_external_ids_connection_ix');
                }
                $table->dropColumn('connection_id');
            });
        }
    }

    private function indexExists(string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $table = self::TABLE;

        return match ($driver) {
            'mysql', 'mariadb' => (bool) $connection->selectOne(
                'SELECT 1 AS present FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $index],
            ),
            'pgsql' => (bool) $connection->selectOne(
                'SELECT 1 AS present FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $index],
            ),
            'sqlite' => $this->sqliteIndexExists($index),
            'sqlsrv' => (bool) $connection->selectOne(
                'SELECT TOP 1 1 AS present FROM sys.indexes WHERE object_id = OBJECT_ID(?) AND name = ?',
                [$table, $index],
            ),
            default => false,
        };
    }

    private function sqliteIndexExists(string $index): bool
    {
        foreach (Schema::getConnection()->select("PRAGMA index_list('" . self::TABLE . "')") as $row) {
            if ((string) ($row->name ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
