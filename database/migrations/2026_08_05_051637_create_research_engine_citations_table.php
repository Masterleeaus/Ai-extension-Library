<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_engine_citations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->text('source');
            $table->json('metadata');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_engine_citations');
    }
};