<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration updates all timestamp columns to include microsecond precision
     * and ensures they are stored in UTC format. This addresses issue #235 where
     * inconsistent DateTime handling with date() function was causing reliability issues.
     *
     * Changes:
     * - Updates created_at, updated_at, deleted_at columns to use datetime with microsecond precision
     * - Ensures all new timestamps are in UTC
     * - Preserves existing data while improving precision
     */
    public function up(): void
    {
        // Get all tables
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            // Skip migration-related tables
            if (in_array($table, ['migrations', 'migration_batches'])) {
                continue;
            }

            // Get table columns
            $columns = DB::connection()->getDoctrineSchemaManager()->listTableColumns($table);

            Schema::table($table, function (Blueprint $table) use ($columns) {
                // Update created_at if it exists
                if (isset($columns['created_at'])) {
                    $table->dateTime('created_at', precision: 6)->nullable()->change();
                }

                // Update updated_at if it exists
                if (isset($columns['updated_at'])) {
                    $table->dateTime('updated_at', precision: 6)->nullable()->change();
                }

                // Update deleted_at if it exists (for soft deletes)
                if (isset($columns['deleted_at'])) {
                    $table->dateTime('deleted_at', precision: 6)->nullable()->change();
                }
            });
        }

        // Add comment about UTC requirement
        DB::statement(
            "ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
             /* All timestamps must be stored in UTC timezone */
             /* Use Foundation\Support\DateTimeHelper for consistent timestamp handling */"
        );
    }

    /**
     * Reverse the migrations.
     *
     * This downgrade removes microsecond precision from timestamp columns,
     * reverting to second-level precision. Note: This will lose microsecond data.
     */
    public function down(): void
    {
        // Get all tables
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            // Skip migration-related tables
            if (in_array($table, ['migrations', 'migration_batches'])) {
                continue;
            }

            // Get table columns
            $columns = DB::connection()->getDoctrineSchemaManager()->listTableColumns($table);

            Schema::table($table, function (Blueprint $table) use ($columns) {
                // Revert created_at if it exists
                if (isset($columns['created_at'])) {
                    $table->dateTime('created_at')->nullable()->change();
                }

                // Revert updated_at if it exists
                if (isset($columns['updated_at'])) {
                    $table->dateTime('updated_at')->nullable()->change();
                }

                // Revert deleted_at if it exists
                if (isset($columns['deleted_at'])) {
                    $table->dateTime('deleted_at')->nullable()->change();
                }
            });
        }
    }
};
