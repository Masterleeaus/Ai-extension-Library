<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Reviews table
        Schema::create('ecommerce_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('product_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('rating');
            $table->string('title');
            $table->text('comment')->nullable();
            $table->boolean('verified_purchase')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->integer('unhelpful_count')->default(0);
            $table->string('status')->default('pending');
            $table->string('moderated_by')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'rating']);
            $table->index(['customer_id', 'status']);
        });

        // Return Requests table
        Schema::create('ecommerce_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('order_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('return_reason');
            $table->date('return_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_status')->default('pending');
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['customer_id', 'return_date']);
        });

        // Recommendation Engine table
        Schema::create('ecommerce_recommendation_engines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('product_id');
            $table->string('recommendation_type');
            $table->decimal('score', 5, 3);
            $table->string('reason')->nullable();
            $table->string('algorithm_used')->nullable();
            $table->boolean('is_clicked')->default(false);
            $table->boolean('is_purchased')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'product_id']);
            $table->index(['recommendation_type', 'score']);
        });

        // Wish Lists table
        Schema::create('ecommerce_wish_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('product_id');
            $table->string('list_name')->nullable();
            $table->boolean('is_public')->default(false);
            $table->decimal('price_at_save', 10, 2)->nullable();
            $table->decimal('current_price', 10, 2)->nullable();
            $table->date('added_date');
            $table->string('priority')->default('medium');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'product_id']);
            $table->index(['is_public', 'added_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_wish_lists');
        Schema::dropIfExists('ecommerce_recommendation_engines');
        Schema::dropIfExists('ecommerce_return_requests');
        Schema::dropIfExists('ecommerce_product_reviews');
    }
};
