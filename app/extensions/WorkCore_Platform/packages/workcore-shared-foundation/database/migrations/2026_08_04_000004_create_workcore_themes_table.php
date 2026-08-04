<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_themes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name')->index();
            $table->string('primary_color');
            $table->string('secondary_color');
            $table->string('accent_color');
            $table->string('font_family')->default('system-ui, -apple-system, sans-serif');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->longText('custom_css')->nullable();
            $table->boolean('is_dark_mode')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'version']);
            $table->index(['tenant_id', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_themes');
    }
};
