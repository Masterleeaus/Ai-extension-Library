# Vertical Customization

Comprehensive customization framework for AI suite extensions including prompts, templates, forms, localization, branding, and behavior configuration.

## Features

- **Prompt Customization**: Create and manage custom AI prompts with versioning
- **Template Management**: Build and manage response, document, and email templates
- **Forms Builder**: Create custom forms with validation and conditional logic
- **Localization**: Multi-language support with regional settings and RTL
- **Branding**: Customize colors, fonts, logos, and themes
- **Behavior Configuration**: Configure model parameters and AI behavior

## Installation

```bash
composer install
```

## Configuration

All customization frameworks are configured through this central extension.

## Usage

```php
// Prompt customization
$promptService = app(\App\Extensions\VerticalCustomization\System\Prompts\Services\PromptComposer::class);

// Template management
$templateService = app(\App\Extensions\VerticalCustomization\System\Templates\TemplateManagementProvider::class);

// Forms builder
$formsService = app(\App\Extensions\VerticalCustomization\System\Forms\FormsBuilderProvider::class);

// Localization
$localizationService = app(\App\Extensions\VerticalCustomization\System\Localization\LocalizationProvider::class);

// Branding
$brandingService = app(\App\Extensions\VerticalCustomization\System\Branding\BrandingCustomizationProvider::class);

// Behavior
$behaviorService = app(\App\Extensions\VerticalCustomization\System\Behavior\BehaviorCustomizationProvider::class);
```

## Testing

```bash
php artisan test
```
