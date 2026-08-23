<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frontend_channel_settings', function (Blueprint $table) {
            // Add multi-tenant fields if they don't exist
            if (!Schema::hasColumn('frontend_channel_settings', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('tz_companies')->cascadeOnDelete()->after('id');
            }
            if (!Schema::hasColumn('frontend_channel_settings', 'user_id')) {
                $table->string('user_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('frontend_channel_settings', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete()->after('user_id');
            }
            
            // Add indexes for multi-tenant queries
            if (!Schema::hasColumn('frontend_channel_settings', 'company_id')) {
                $table->index(['company_id']);
            }
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('frontend_channel_settings', function (Blueprint $table) {
            $table->dropIndex(['frontend_channel_settings_company_id_index']);
            $table->dropIndex(['frontend_channel_settings_company_id_user_id_index']);
            $table->dropForeign(['frontend_channel_settings_company_id_foreign']);
            $table->dropColumn(['company_id', 'user_id', 'team_id']);
        });
    }
};