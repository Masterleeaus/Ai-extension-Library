# AIAgent Extension

Autonomous AI Agent extension with WorkCore integration, governance controls, and action approval workflows.

## Features

- **WorkCore Integration**: Full integration with WorkCore context, operations, and workflows
- **Autonomous Execution**: Support for autonomous action execution with governance
- **Action Approval**: Financial and sensitive actions require explicit approval
- **Rate Limiting**: Configurable rate limits per action type
- **Governed Actions**: High-risk actions trigger governance workflows
- **Workflow Management**: Execute and track autonomous workflows

## Installation

```bash
composer install
```

## Configuration

Configure rate limits and governed actions in the extension configuration.

## Usage

```php
$aiAgent = app(AIAgentWorkCoreIntegration::class);

// Check if action can be executed
if ($aiAgent->canExecuteWorkflow('process_payment')) {
    // Execute workflow
}

// Get context for autonomous operations
$context = $aiAgent->getWorkCoreContext();

// Check rate limits
$budget = $aiAgent->getRateLimitBudget('api_call');
```

## Testing

```bash
php artisan test
```
