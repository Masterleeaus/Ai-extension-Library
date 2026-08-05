<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills_runtime_executions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('skill_id');
            $table->string('status');
            $table->json('result')->nullable();
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills_runtime_executions');
    }
};