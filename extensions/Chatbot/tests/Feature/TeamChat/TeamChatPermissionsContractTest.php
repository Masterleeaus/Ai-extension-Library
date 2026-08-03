<?php

declare(strict_types=1);

namespace Tests\Feature\TeamChat;

use PHPUnit\Framework\TestCase;

final class TeamChatPermissionsContractTest extends TestCase
{
    public function test_pass_two_contains_role_and_realtime_contracts(): void
    {
        $root = dirname(__DIR__, 3);
        $policy = file_get_contents($root.'/System/Policies/TeamChat/TeamConversationPolicy.php');
        $provider = file_get_contents($root.'/System/ChatbotServiceProvider.php');
        $client = file_get_contents($root.'/resources/assets/js/team-chat/team-chat-client.js');

        self::assertStringContainsString("['owner', 'admin']", $policy);
        self::assertStringContainsString("['owner', 'admin', 'moderator']", $policy);
        self::assertStringContainsString('registerTeamChatBroadcastChannels', $provider);
        self::assertStringContainsString('participants/{participant}', $provider);
        self::assertStringContainsString(".listen('.team.user.typing'", $client);
        self::assertStringContainsString('changeParticipantRole', $client);
    }
}
