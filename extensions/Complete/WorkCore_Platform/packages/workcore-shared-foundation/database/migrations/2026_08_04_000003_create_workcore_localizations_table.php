<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_localizations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('language')->index();
            $table->string('region')->nullable()->index();
            $table->json('translations')->nullable();
            $table->json('regional_settings')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'language', 'region', 'version']);
            $table->index(['tenant_id', 'language', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_localizations');
    }
};
