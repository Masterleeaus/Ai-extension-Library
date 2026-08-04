<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logs all interactions for improvement analysis
        Schema::create('interaction_improvement_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('user_id');
            $table->text('input_message');
            $table->enum('model_used', ['cloud-claude', 'localbrain-v2'])->default('localbrain-v2');
            $table->string('action')->nullable();
            $table->decimal('confidence', 3, 2)->default(0.0);
            $table->json('result_payload')->nullable();
            $table->decimal('feedback_score', 3, 2)->nullable();
            $table->json('improvement_suggestion')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('error_analyzed')->default(false);
            $table->json('analysis_result')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'model_used']);
            $table->index(['confidence']);
            $table->index(['error_analyzed']);
            $table->index('created_at');
        });

        // Stores prompt refinement suggestions from cloud analysis
        Schema::create('prompt_refinement_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->json('suggestions');
            $table->enum('status', ['suggested', 'reviewed', 'applied'])->default('suggested');
            $table->text('review_notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        // Tracks model performance comparison
        Schema::create('model_performance_comparison', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->enum('model_type', ['cloud-claude', 'localbrain-v2'])->default('localbrain-v2');
            $table->integer('total_interactions')->default(0);
            $table->decimal('avg_confidence', 3, 2)->default(0.0);
            $table->decimal('success_rate', 5, 2)->default(0.0);
            $table->integer('low_confidence_count')->default(0);
            $table->decimal('avg_response_time_ms', 8, 2)->default(0.0);
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'model_type']);
            $table->index('analyzed_at');
        });

        // Cloud model feedback and improvements applied
        Schema::create('cloud_improvement_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('improvement_type'); // intent, memory, prompt, error
            $table->json('original_data');
            $table->json('suggested_improvement');
            $table->json('cloud_analysis');
            $table->enum('status', ['pending', 'applied', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'improvement_type']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interaction_improvement_logs');
        Schema::dropIfExists('prompt_refinement_suggestions');
        Schema::dropIfExists('model_performance_comparison');
        Schema::dropIfExists('cloud_improvement_feedback');
    }
};
