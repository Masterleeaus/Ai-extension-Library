<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_engine_synthesis', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->text('text');
            $table->text('audio_url');
            $table->string('voice_id');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_engine_synthesis');
    }
};