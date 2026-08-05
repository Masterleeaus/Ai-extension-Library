<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_ownership_registry', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->text('file_path');
            $table->string('owner_id');
            $table->string('access_level');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_ownership_registry');
    }
};