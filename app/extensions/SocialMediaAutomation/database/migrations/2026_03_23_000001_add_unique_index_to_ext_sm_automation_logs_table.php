<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_sm_automation_logs', function (Blueprint $table) {
            $table->unique(['automation_id', 'platform_comment_id'], 'automation_comment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ext_sm_automation_logs', function (Blueprint $table) {
            $table->dropUnique('automation_comment_unique');
        });
    }
};
