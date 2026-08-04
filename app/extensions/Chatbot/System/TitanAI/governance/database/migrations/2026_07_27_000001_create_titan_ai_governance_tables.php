<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('titan_ai_approval_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('user_id')->index();
            $table->string('device_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('risk_level', 16)->index();
            $table->json('payload');
            $table->json('council_result');
            $table->string('status', 16)->default('pending')->index();
            $table->string('resolved_by')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('titan_ai_action_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('user_id')->index();
            $table->string('action')->index();
            $table->string('status', 24)->index();
            $table->string('risk_level', 16)->index();
            $table->uuid('approval_id')->nullable()->index();
            $table->json('council_result');
            $table->json('workcore_result')->nullable();
            $table->json('rollback_contract')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titan_ai_action_receipts');
        Schema::dropIfExists('titan_ai_approval_requests');
    }
};
