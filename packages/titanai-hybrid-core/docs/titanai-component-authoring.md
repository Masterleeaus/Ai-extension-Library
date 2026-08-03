# Adding TitanAI Components

## Add a Chatbot bundled skill

1. Add `SKILL.md` beneath `app/Extensions/Chatbot/System/TitanAI/skills/bundled/<slug>/`.
2. Add the slug, relative path, version, and SHA-256 to `bundled-skills.json`.
3. Run:

```bash
php bin/verify-titanai-pass2.php
```

`FieldServiceSkillRegistry` verifies the file hash before `UnifiedSkillAdapter` exposes it. The unified adapter is a governed-context entry, not a raw prompt endpoint: execute the skill through `FieldServiceSkillContextProvider` so internal instructions are never returned to callers.

## Add an AI Agent action

1. Implement `AIAgentActionInterface` using the existing workflow execution signature.
2. Register the class in `AIAgentServiceProvider::registerBuiltInActions()` or from an independent extension provider:

```php
app(AIAgentActionRegistry::class)->register('example', ExampleAction::class);
```

3. Registration is mirrored automatically into `UnifiedRegistry`, including actions registered after AI Agent boots.

The shared adapter uses the action key, label, description, category, icon, and config schema automatically. Unified execution requires this context:

```php
$registry->getAction('example')->execute(
    ['config' => $actionConfig],
    ['workflow' => $workflow, 'run' => $run, 'state' => $context],
);
```

A repeated same-key/same-class registration is idempotent. A same-key/different-class registration is rejected to prevent native/unified registry drift.

## Add an AIChatPro connector

1. Implement the native `App\Extensions\AIChatPro\System\Connectors\ConnectorDefinition` contract in an independent connector extension.
2. Register it with the native registry:

```php
app(ConnectorRegistry::class)->register('provider-key', ProviderConnector::class);
```

`ConnectorRegistry::onRegistered()` mirrors it into `UnifiedRegistry`, including connectors registered after AIChatPro boots. A conflicting replacement under the same native key is rejected.

## Direct foundation registration

New component types may implement one of:

- `SkillDefinition`
- `ActionDefinition`
- `ConnectorDefinition`
- `ToolDefinition`

Then register through:

```php
app(UnifiedRegistry::class)->register($component);
```

Duplicate unified keys are rejected unless `titanai.registry.allow_overrides` is enabled. Native AI Agent action and AIChatPro connector registries remain stricter and do not permit same-key/different-class replacement, even when unified overrides are enabled.
