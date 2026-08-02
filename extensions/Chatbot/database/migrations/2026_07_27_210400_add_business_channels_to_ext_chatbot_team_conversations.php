<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ext_chatbot_team_conversations', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('name');
            $table->string('category', 120)->nullable()->after('description');
            $table->string('posting_policy', 32)->default('members')->after('visibility');
            $table->boolean('is_announcement')->default(false)->after('posting_policy');
            $table->string('linked_entity_type', 120)->nullable()->after('is_announcement');
            $table->string('linked_entity_id', 191)->nullable()->after('linked_entity_type');
            $table->timestamp('channel_archived_at')->nullable()->after('linked_entity_id');
            $table->unique(['tenant_id', 'slug'], 'ext_chatbot_team_channels_tenant_slug_unique');
            $table->index(['tenant_id', 'type', 'visibility'], 'ext_chatbot_team_channel_discovery_idx');
            $table->index(['tenant_id', 'linked_entity_type', 'linked_entity_id'], 'ext_chatbot_team_linked_room_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbot_team_conversations', function (Blueprint $table): void {
            $table->dropUnique('ext_chatbot_team_channels_tenant_slug_unique');
            $table->dropIndex('ext_chatbot_team_channel_discovery_idx');
            $table->dropIndex('ext_chatbot_team_linked_room_idx');
            $table->dropColumn(['slug','category','posting_policy','is_announcement','linked_entity_type','linked_entity_id','channel_archived_at']);
        });
    }
};
