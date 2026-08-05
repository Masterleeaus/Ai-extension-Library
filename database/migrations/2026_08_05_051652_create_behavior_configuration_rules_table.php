<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_configuration_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->json('condition');
            $table->json('action');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_configuration_rules');
    }
};