<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_engine_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('topic');
            $table->json('messages');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_engine_conversations');
    }
};