# TitanAI Pass 3 Operations

## Service aliases

```php
app('titanai.registry');
app('titanai.memory');
app('titanai.events');
app('titanai.diagnostics');
app('titanai.orchestrator');
```

## Structured action execution

```php
$result = app('titanai.orchestrator')->executeAction(
    'generate_report',
    ['config' => ['format' => 'summary']],
    [
        'workflow' => $workflow,
        'run' => $run,
        'state' => $state,
        'correlation_id' => (string) Str::uuid(),
    ],
);

if (! $result['ok']) {
    // Branch on $result['error_code']; do not parse the message.
}
```

## Connector delivery

```php
$result = app('titanai.orchestrator')->send('gmail', [
    'connector' => $connectorModel,
    'function_name' => 'connector_gmail_send_message',
    'arguments' => ['to' => 'recipient@example.test'],
]);
```

The connector must be installed, registered, and configured. The base AIChatPro archive does not include provider implementations.

## Diagnostics

```php
$diagnostics = app('titanai.diagnostics')->snapshot(app('titanai.registry'));
```

The snapshot intentionally excludes prompts, memory values, connector credentials, raw event idempotency keys, and exception messages. It contains bounded counters and recent operation summaries.

## Memory cleanup

```bash
php artisan titanai:memory:purge
```

The foundation schedules this command hourly when automatic cleanup is enabled. Confirm the host scheduler is running in Pass 4.

## Deployment sequence

1. Apply the Pass 3 delta over the verified Pass 2 tree.
2. Run `composer dump-autoload` in the complete WorkCore host.
3. Run migrations before enabling the AI Agent memory bridge.
4. Keep bridge strict mode disabled for the first host smoke test.
5. Run `php artisan titanai:memory:purge` manually.
6. Inspect the authenticated runtime diagnostics endpoint.
7. Enable scheduled cleanup only after the command succeeds.
