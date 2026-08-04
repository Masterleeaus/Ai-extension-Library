<?php

declare(strict_types=1);

namespace Tests\Unit\Extensions\Chatbot\StaffInbox;

use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\StaffInboxController;
use App\Extensions\Chatbot\System\Services\StaffInbox\ChatbotForPanelEventAbly;
use PHPUnit\Framework\TestCase;

final class StaffInboxNamespaceTest extends TestCase
{
    public function test_staff_inbox_classes_live_inside_chatbot_extension(): void
    {
        self::assertSame(
            'App\\Extensions\\Chatbot\\System\\Http\\Controllers\\StaffInbox\\StaffInboxController',
            StaffInboxController::class,
        );

        self::assertSame(
            'App\\Extensions\\Chatbot\\System\\Services\\StaffInbox\\ChatbotForPanelEventAbly',
            ChatbotForPanelEventAbly::class,
        );
    }
}
