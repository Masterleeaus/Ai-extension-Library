<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_registry', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('migration_name');
            $table->string('status');
            $table->json('progress');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_registry');
    }
};