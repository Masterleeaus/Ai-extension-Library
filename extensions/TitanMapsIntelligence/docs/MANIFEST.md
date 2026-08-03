# Extension Manifest

- Schema: `2.0`
- ID: `titan-maps-intelligence`
- Type: `integration`
- Provider: `App\Extensions\TitanMapsIntelligence\TitanMapsIntelligenceServiceProvider`
- Default: disabled
- Version: `1.0.0`

The SDK's allowed extension type is `integration`; no custom `intelligence` type is introduced.

## Host requirements

Identity, companies, memberships, permissions, active-company context, queues, audit, Titan Vault, extension registry, capability registry, private signed-export storage, and WorkCore CRM/provider/supplier services.

## Settings

Manifest settings contain operational defaults and a credential-reference field only. No API key default is present. Runtime provider configuration is company scoped through `maps_provider_connections`.

## Capability count

Version 1.0.0 publishes 12 Titan Quattro capabilities, including the governed search export capability.
