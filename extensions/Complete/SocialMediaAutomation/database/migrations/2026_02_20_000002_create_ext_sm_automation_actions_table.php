<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_sm_automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('ext_sm_automations')->cascadeOnDelete();
            $table->string('type');
            $table->json('content');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['automation_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_sm_automation_actions');
    }
};
