<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_sm_pending_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('ext_sm_automations')->cascadeOnDelete();
            $table->json('comment_data');
            $table->timestamp('execute_at');
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'execute_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_sm_pending_automations');
    }
};
