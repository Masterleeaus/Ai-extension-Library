<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('error_logs')) {
            return;
        }

        Schema::create('error_logs', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('tenant_id', 32)->nullable()->index();
            $table->string('user_id', 32)->nullable()->index();
            $table->string('operation', 50)->index();
            $table->text('error_message');
            $table->string('sql_state', 5)->nullable()->index();
            $table->boolean('is_transient')->default(false)->index();
            $table->json('context')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->timestamp('logged_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();

            // Create composite indexes for common queries
            $table->index(['tenant_id', 'logged_at']);
            $table->index(['operation', 'is_transient']);
            $table->index(['sql_state', 'is_transient']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
