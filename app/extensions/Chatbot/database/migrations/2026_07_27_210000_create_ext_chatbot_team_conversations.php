<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ext_chatbot_team_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name')->nullable();
            $table->enum('type', ['direct', 'group', 'channel', 'ai'])->default('direct')->index();
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->enum('visibility', ['private', 'tenant', 'public'])->default('private');
            $table->boolean('is_archived')->default(false)->index();
            $table->unsignedBigInteger('version')->default(1);
            $table->unsignedBigInteger('sync_sequence')->default(0)->index();
            $table->string('originating_device_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'type', 'updated_at'], 'chatbot_team_conversation_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_team_conversations');
    }
};
