<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_sm_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('ext_sm_automations')->cascadeOnDelete();
            $table->string('platform_comment_id')->nullable();
            $table->string('commenter_id')->nullable();
            $table->string('commenter_username')->nullable();
            $table->string('comment_text')->nullable();
            $table->json('actions_executed')->nullable();
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('automation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_sm_automation_logs');
    }
};
