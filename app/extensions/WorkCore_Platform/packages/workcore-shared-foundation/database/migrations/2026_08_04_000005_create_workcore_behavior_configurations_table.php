<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_behavior_configurations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name')->index();
            $table->decimal('model_temperature', 3, 2)->default(0.70);
            $table->decimal('top_p', 3, 2)->default(1.00);
            $table->unsignedInteger('max_tokens')->default(2048);
            $table->string('response_style')->default('conversational');
            $table->string('tone')->default('professional');
            $table->json('personality_traits')->nullable();
            $table->json('guardrails')->nullable();
            $table->json('content_filter')->nullable();
            $table->boolean('bias_detection')->default(true);
            $table->boolean('hallucination_prevention')->default(true);
            $table->json('retry_strategy')->nullable();
            $table->string('fallback_behavior')->default('graceful_degradation');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'version']);
            $table->index(['tenant_id', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_behavior_configurations');
    }
};
