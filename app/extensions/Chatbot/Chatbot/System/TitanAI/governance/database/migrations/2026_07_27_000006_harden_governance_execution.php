<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('titan_ai_approval_requests', function (Blueprint $table): void {
            $table->unsignedInteger('execution_attempts')->default(0)->after('status');
            $table->timestamp('execution_started_at')->nullable()->after('execution_attempts');
            $table->text('last_execution_error')->nullable()->after('execution_started_at');
        });
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->text('failure_reason')->nullable()->after('workcore_result');
        });
    }

    public function down(): void
    {
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->dropColumn('failure_reason');
        });
        Schema::table('titan_ai_approval_requests', function (Blueprint $table): void {
            $table->dropColumn(['execution_attempts', 'execution_started_at', 'last_execution_error']);
        });
    }
};
