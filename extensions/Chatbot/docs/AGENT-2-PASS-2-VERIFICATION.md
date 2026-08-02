# Agent 2 pass 2 verification

## Static verification

- All PHP files under `System`, `database` and `tests` pass `php -l`.
- `resources/pwa/chatbot-pwa/sync/engine.js` passes `node --check`.

## Host-application verification still required

Run inside the complete Laravel application after installing the extension:

```bash
php artisan migrate
php artisan route:list --path=api/v2/chatbot
php artisan test --filter=SyncContractTest
```

Then exercise:

1. Register two devices for one user.
2. Create a local conversation and dependent message.
3. Push the batch in reverse order and verify dependency ordering.
4. Replay the exact batch and verify no duplicate records or events.
5. Update the same record on both devices and verify a structured 412 conflict.
6. Delete a record and verify the pull stream returns a tombstone.
7. Pull `conversation`, `message` and `customer` scopes independently and verify cursors do not skip changes.
8. Attempt cross-user IDs and verify they are rejected.
