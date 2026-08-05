<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_vault_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('key');
            $table->text('secret');
            $table->string('type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->timestamps();
            $table->timestamp('rotated_at')->nullable();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_vault_credentials');
    }
};