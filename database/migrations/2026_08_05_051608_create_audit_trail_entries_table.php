<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_trail_entries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('action');
            $table->string('resource_type');
            $table->string('resource_id');
            $table->json('changes');
            $table->string('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trail_entries');
    }
};