# Titan Migration Engine

Titan Migration Engine upgrades the existing `Migration` extension into extensible migration infrastructure while preserving installed technical identity.

## Compatibility retained

- Folder: `Migration`
- Namespace: `App\Extensions\Migration`
- Provider: `MigrationServiceProvider`
- Config namespace: `migration`
- Legacy routes: `migration::welcome`, `migration::start`, `migration::migrate`
- Legacy source value: `davinci`
- Existing migrations and external ID columns

## Foundation in this release

- Source connector contract
- Immutable connector metadata
- Deterministic connector registry
- Dedicated duplicate and unknown connector errors
- Davinci registration through a legacy adapter
- Existing `MigrationService` and driver workflow retained unchanged

The legacy synchronous import path remains available only for compatibility. It is not yet the safe queued migration pipeline described in the full Titan Migration Engine roadmap.

## Focused verification

```bash
php app/extensions/Migration/tests/ConnectorRegistryTest.php
find app/extensions/Migration -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Support

Telegram: https://t.me/heew_support
