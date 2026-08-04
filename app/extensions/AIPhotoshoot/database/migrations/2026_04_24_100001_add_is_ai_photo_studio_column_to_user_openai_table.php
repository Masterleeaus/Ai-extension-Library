<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_openai', function (Blueprint $table) {
            $table->boolean('is_ai_photo_studio')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_openai', function (Blueprint $table) {
            $table->dropIndex(['is_ai_photo_studio']);
            $table->dropColumn('is_ai_photo_studio');
        });
    }
};
