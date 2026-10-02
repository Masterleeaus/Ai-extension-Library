<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ext_chatbot_booking_slots')) {
            Schema::create('ext_chatbot_booking_slots', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('chatbot_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('variant_id')->nullable()->index();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->timestampTz('starts_at');
                $table->timestampTz('ends_at');
                $table->string('timezone', 64)->default('UTC');
                $table->unsignedInteger('capacity')->default(1);
                $table->unsignedInteger('reserved_capacity')->default(0);
                $table->unsignedInteger('price_amount')->nullable();
                $table->char('currency', 3)->default('AUD');
                $table->string('status', 24)->default('open')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['chatbot_id', 'status', 'starts_at']);
            });
        }

        if (! Schema::hasTable('ext_chatbot_bookings')) {
            Schema::create('ext_chatbot_bookings', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('chatbot_id')->index();
                $table->unsignedBigInteger('slot_id')->index();
                $table->unsignedBigInteger('customer_identity_id')->nullable()->index();
                $table->string('session_id', 191)->index();
                $table->unsignedInteger('quantity')->default(1);
                $table->string('status', 24)->default('confirmed')->index();
                $table->string('idempotency_key', 191);
                $table->json('customer_details')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['chatbot_id', 'idempotency_key'], 'commerce_booking_idempotency_unique');
                $table->index(['chatbot_id', 'session_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Bookings are commerce records. Uninstall and rollback retain them for audit.
    }
};
