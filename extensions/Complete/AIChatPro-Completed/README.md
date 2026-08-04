# AIChatPro Extension

Advanced AI Chat Professional extension with WorkCore integration, skill execution, and conversation management.

## Features

- **WorkCore Integration**: Full integration with WorkCore context and operations
- **Skill Execution**: Execute tools and skills with permission checks
- **Conversation Management**: Resolve and access conversation contexts
- **File Chat**: Support for file-based chat operations
- **Authorization**: Fine-grained tool and skill authorization
- **Denial Reasoning**: Detailed denial reasons for permission failures

## Installation

```bash
composer install
```

## Configuration

Configure tools, skills, and authorization policies through extension configuration.

## Usage

```php
$chatPro = app(AiChatProWorkCoreIntegration::class);

// Check if skill can be executed
if ($chatPro->canExecuteSkill('summarize', ['text' => '...'])) {
    // Execute skill
}

// Resolve conversation context
$context = $chatPro->resolveConversationContext($conversationId);

// Access file chat
if ($chatPro->canAccessFileChat($folderId)) {
    // Access files in chat
}
```

## Testing

```bash
php artisan test
```
