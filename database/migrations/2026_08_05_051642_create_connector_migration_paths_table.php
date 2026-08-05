<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_migration_paths', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('from_connector');
            $table->string('to_connector');
            $table->json('mapping');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_migration_paths');
    }
};