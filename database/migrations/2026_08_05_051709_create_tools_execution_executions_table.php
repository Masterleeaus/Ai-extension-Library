<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_execution_executions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('tool_id');
            $table->json('input');
            $table->json('output')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_execution_executions');
    }
};