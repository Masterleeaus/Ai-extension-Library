<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_engine_executions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('workflow_id');
            $table->string('status');
            $table->json('context');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_engine_executions');
    }
};