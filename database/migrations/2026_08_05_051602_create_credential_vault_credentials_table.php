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
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_vault_credentials');
    }
};