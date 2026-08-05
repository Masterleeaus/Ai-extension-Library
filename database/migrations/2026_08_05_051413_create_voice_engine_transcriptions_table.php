<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_engine_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('session_id');
            $table->longText('text');
            $table->decimal('confidence');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_engine_transcriptions');
    }
};