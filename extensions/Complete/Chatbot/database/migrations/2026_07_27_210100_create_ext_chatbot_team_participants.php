<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ext_chatbot_team_conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ext_chatbot_team_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('role', ['owner', 'admin', 'moderator', 'member', 'guest'])->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['conversation_id', 'user_id'], 'chatbot_team_participant_unique');
            $table->index(['tenant_id', 'user_id', 'archived_at'], 'chatbot_team_participant_inbox');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_team_conversation_participants');
    }
};
