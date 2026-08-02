<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_ai_chat_memories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('worker_id')->nullable()->index();
            $table->string('scope_type', 40)->index();
            $table->string('scope_id')->nullable()->index();
            $table->string('type', 40)->index();
            $table->string('fingerprint', 64);
            $table->json('content');
            $table->json('source')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('importance', 5, 4)->default(0.5);
            $table->decimal('confidence', 5, 4)->default(0.5);
            $table->unsignedInteger('recall_count')->default(0);
            $table->boolean('pinned')->default(false);
            $table->boolean('requires_consent')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('last_recalled_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'fingerprint']);
        });
    }
    public function down(): void { Schema::dropIfExists('system_ai_chat_memories'); }
};
