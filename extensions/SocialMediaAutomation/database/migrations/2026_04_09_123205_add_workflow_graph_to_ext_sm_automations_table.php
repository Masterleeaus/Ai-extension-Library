<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_sm_automations', function (Blueprint $table) {
            $table->json('workflow_graph')->nullable()->after('delay_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('ext_sm_automations', function (Blueprint $table) {
            $table->dropColumn('workflow_graph');
        });
    }
};
