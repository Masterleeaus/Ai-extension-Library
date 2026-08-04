<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Chatbot\StaffInbox;

use Tests\TestCase;

final class StaffInboxRoutesTest extends TestCase
{
    public function test_staff_inbox_routes_are_registered_by_chatbot_extension(): void
    {
        self::assertTrue(app('router')->has('dashboard.chatbot.inbox.index'));
        self::assertTrue(app('router')->has('dashboard.chatbot.inbox.history'));
        self::assertTrue(app('router')->has('dashboard.chatbot.inbox.conversations.with.paginate'));
        self::assertTrue(app('router')->has('dashboard.admin.settings.chatbot-realtime.index'));
    }
}
