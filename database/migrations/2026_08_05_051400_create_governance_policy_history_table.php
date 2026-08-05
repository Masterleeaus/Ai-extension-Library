<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_policy_history', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('policy_id');
            $table->json('changes');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_policy_history');
    }
};