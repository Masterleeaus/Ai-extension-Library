# Chatbot Extension

Progressive Web App (PWA) Chatbot extension with WorkCore integration, connector management, and offline support.

## Features

- **WorkCore Integration**: Full integration with WorkCore context and operations
- **Connector Management**: Use external connectors with permission checks
- **Knowledge Ingestion**: Support for knowledge base ingestion
- **Offline Support**: Full PWA capability for offline operation
- **Localization**: Multi-language and timezone support
- **Progressive Web App**: Works offline with sync capabilities

## Installation

```bash
composer install
```

## Configuration

Configure connectors, offline storage, and language support through extension configuration.

## Usage

```php
$chatbot = app(ChatbotWorkCoreIntegration::class);

// Check connector access
if ($chatbot->canUseConnector('twilio', ['account_id' => '...'])) {
    // Use connector
}

// Ingest knowledge
if ($chatbot->canIngestKnowledge('documents')) {
    // Ingest knowledge
}

// Check offline capability
if ($chatbot->canGoOffline()) {
    // Enable offline mode
}
```

## Testing

```bash
php artisan test
```
