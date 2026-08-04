<?php

declare(strict_types=1);

it('keeps team chat inside the chatbot extension namespace', function (): void {
    $root = dirname(__DIR__, 3);
    $files = [
        $root . '/System/Models/TeamChat/TeamConversation.php',
        $root . '/System/Models/TeamChat/TeamMessage.php',
        $root . '/System/Services/TeamChat/TeamConversationService.php',
    ];

    foreach ($files as $file) {
        expect(file_exists($file))->toBeTrue();
        expect(file_get_contents($file))->toContain('App\\Extensions\\Chatbot\\System');
        expect(file_get_contents($file))->not->toContain('namespace App\\Models');
    }
});

it('does not import donor destructive delete or public maintenance routes', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 3) . '/System/ChatbotServiceProvider.php');
    expect($provider)->not->toContain('/system/catch-clear');
    expect($provider)->not->toContain('/system/storage-link');
});
