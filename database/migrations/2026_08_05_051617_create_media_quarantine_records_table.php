<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_quarantine_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->text('file_path');
            $table->text('reason');
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_quarantine_records');
    }
};