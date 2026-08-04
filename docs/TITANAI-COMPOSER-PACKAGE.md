# TitanAI Hybrid Core Composer Package

## Status

The former `foundation/TitanAI-Hybrid` host overlay has been converted into the private nested Composer package:

```text
packages/titanai-hybrid-core/
```

Composer identity:

```text
titanai/hybrid-core:1.0.0
```

The package is the shared foundation required by AIAgent, AIChatPro, and Chatbot. It is not a fourth marketplace extension.

## Installation in WorkCore

During monorepo development, add a path repository to the WorkCore root `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../Ai-extensions/packages/titanai-hybrid-core",
      "options": {
        "symlink": true,
        "reference": "config"
      }
    }
  ],
  "require": {
    "titanai/hybrid-core": "^1.0"
  }
}
```

Then run Composer from the WorkCore root. Normal marketplace requests must not edit the root Composer configuration or download dependencies dynamically.

## Namespace

New package code uses:

```php
TitanAI\Hybrid\...
```

The three core extensions import this namespace directly. `src/Compatibility/LegacyAliases.php` provides temporary aliases for the shared legacy `App\Domains\TitanAI\...` classes. The aliases do not move or override Chatbot-owned channel, tier, persona, model-routing, or tool classes.

## Extension dependency metadata

Each core extension contains `titanai-package.json` declaring:

- package: `titanai/hybrid-core`;
- constraint: `^1.0`;
- provider: `TitanAI\Hybrid\TitanAIServiceProvider`.

This metadata is intentionally separate from the marketplace `extension.json` schema.

## Materialisation guarantee

The verified MiniUp Pass 3 overlay remains unchanged as the recovery source. The repository workflow restores it and immediately runs:

```bash
python scripts/package_titanai_hybrid.py --root .
```

The converter creates the package, updates shared imports, writes dependency manifests, archives the Pass 2/3 evidence under `docs/archive/titanai-pass3/`, and deletes the old foundation directory. Its `--check` mode fails if the legacy foundation returns, historical reports leak into the installable package, or package wiring drifts.

## Versioning

Use semantic versioning:

- patch: internal fix with no contract change;
- minor: backward-compatible contract or capability addition;
- major: breaking contracts, migrations, namespace removal, or changed extension requirements.

Keep branches short and merge verified package slices into `main` regularly. Tag standalone package releases after complete WorkCore host integration proves Composer resolution, provider boot, migrations, schedules, queues, and authorization boundaries.
