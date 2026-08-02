<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TeamChatBusinessChannelsContractTest extends TestCase
{
    public function test_channel_migration_contains_business_room_fields(): void
    {
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_07_27_210400_add_business_channels_to_ext_chatbot_team_conversations.php');
        foreach (['slug','posting_policy','is_announcement','linked_entity_type','linked_entity_id','channel_archived_at'] as $field) {
            self::assertStringContainsString($field, $migration);
        }
    }

    public function test_channel_service_restricts_type_to_channel(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/TeamChat/BusinessChannelService.php');
        self::assertStringContainsString("'type' => 'channel'", $service);
        self::assertStringContainsString('joinPublic', $service);
        self::assertStringContainsString('posting_policy', $service);
    }
}
