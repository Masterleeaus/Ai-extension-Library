<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->enum('type', ['airbnb', 'booking', 'amazon', 'ebay', 'delivery'])->index();
            $table->text('credentials')->nullable();
            $table->string('api_token')->nullable();
            $table->string('api_secret')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('authenticated_at')->nullable();
            $table->string('auth_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
