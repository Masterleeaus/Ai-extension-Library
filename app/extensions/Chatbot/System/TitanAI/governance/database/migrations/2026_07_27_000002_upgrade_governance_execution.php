<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('titan_ai_approval_requests', function (Blueprint $table): void {
            $table->string('domain')->default('')->after('action')->index();
            $table->json('tool_definition')->nullable()->after('payload');
            $table->json('execution_context')->nullable()->after('tool_definition');
        });
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->json('rollback_result')->nullable()->after('rollback_contract');
            $table->string('rolled_back_by')->nullable()->index();
            $table->timestamp('rolled_back_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->dropColumn(['rollback_result', 'rolled_back_by', 'rolled_back_at']);
        });
        Schema::table('titan_ai_approval_requests', function (Blueprint $table): void {
            $table->dropColumn(['domain', 'tool_definition', 'execution_context']);
        });
    }
};
