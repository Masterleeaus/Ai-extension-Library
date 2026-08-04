<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->string('device_id')->nullable()->index()->after('user_id');
            $table->string('manager_id')->nullable()->index()->after('action');
            $table->string('assistant_id')->nullable()->index()->after('manager_id');
            $table->string('agent_id')->nullable()->index()->after('assistant_id');
            $table->string('conversation_id')->nullable()->index()->after('agent_id');
            $table->string('workflow_id')->nullable()->index()->after('conversation_id');
            $table->string('channel')->nullable()->index()->after('workflow_id');
            $table->string('idempotency_key')->nullable()->index()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->dropIndex(['device_id']);
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['assistant_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['workflow_id']);
            $table->dropIndex(['channel']);
            $table->dropIndex(['idempotency_key']);
            $table->dropColumn([
                'device_id','manager_id','assistant_id','agent_id',
                'conversation_id','workflow_id','channel','idempotency_key',
            ]);
        });
    }
};
