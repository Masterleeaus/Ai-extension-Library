<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('key_name');
            $table->text('public_key');
            $table->text('private_key');
            $table->string('algorithm');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_encryption_keys');
    }
};