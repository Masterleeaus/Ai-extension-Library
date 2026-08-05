<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localization_tenant_locales', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('locale');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localization_tenant_locales');
    }
};