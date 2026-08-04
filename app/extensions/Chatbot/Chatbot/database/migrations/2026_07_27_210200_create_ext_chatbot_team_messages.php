<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ext_chatbot_team_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('conversation_id')->constrained('ext_chatbot_team_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->longText('body')->nullable();
            $table->enum('message_type', ['text', 'system', 'attachment', 'voice', 'event'])->default('text');
            $table->foreignId('reply_to_id')->nullable()->constrained('ext_chatbot_team_messages')->nullOnDelete();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->unsignedBigInteger('sync_sequence')->default(0)->index();
            $table->string('originating_device_id')->nullable()->index();
            $table->timestamp('edited_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['conversation_id', 'created_at', 'id'], 'chatbot_team_message_order');
        });

        Schema::create('ext_chatbot_team_message_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('ext_chatbot_team_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();
            $table->unique(['message_id', 'user_id'], 'chatbot_team_message_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_team_message_reads');
        Schema::dropIfExists('ext_chatbot_team_messages');
    }
};
