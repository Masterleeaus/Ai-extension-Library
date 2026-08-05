<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_context_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('user_id')->nullable();
            $table->string('actor_id')->nullable();
            $table->json('permissions')->nullable();
            $table->integer('policy_version');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_context_contexts');
    }
};