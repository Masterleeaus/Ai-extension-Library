<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('shopping_carts')->cascadeOnDelete();
            $table->uuid('product_id')->index();
            $table->uuid('variant_id')->nullable()->index();
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_time', 15, 2);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
