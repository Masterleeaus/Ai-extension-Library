<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_sm_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_media_platform_id')->constrained('ext_social_media_platforms')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->string('trigger_target')->default('all_posts');
            $table->string('trigger_post_id')->nullable();
            $table->json('trigger_post_data')->nullable();
            $table->string('keyword_mode')->default('any');
            $table->json('include_keywords')->nullable();
            $table->json('exclude_keywords')->nullable();
            $table->boolean('enable_public_replies')->default(false);
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['social_media_platform_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_sm_automations');
    }
};
