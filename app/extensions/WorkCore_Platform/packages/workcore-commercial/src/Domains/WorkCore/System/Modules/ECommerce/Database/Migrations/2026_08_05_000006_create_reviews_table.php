<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('customer_id')->index();
            $table->string('customer_name')->nullable();
            $table->tinyInteger('rating')->between(1, 5);
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->enum('moderation_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->integer('helpful_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['product_id', 'moderation_status']);
            $table->unique(['product_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
