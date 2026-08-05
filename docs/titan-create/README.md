# Titan Create

Titan Create is the vertical AI creative operating system for Titan Zero and independent customers.

## Canonical base extension

`app/extensions/CreativeSuite` is the installation, upgrade and compatibility anchor.

Preserve its existing technical identity unless a verified host constraint requires otherwise:

- install folder and namespace;
- service provider and extension slug;
- route names and configuration keys;
- migration history and `ext_creative_suite_documents`;
- persisted Konva scene JSON, previews and user projects.

Customer-facing branding may become **Titan Create**, but technical identifiers are not renamed solely for branding.

## Canonical capability ownership

| Capability | Canonical owner | Donor/adapters |
|---|---|---|
| Workspace, editor and projects | CreativeSuite | CreativeSuiteAITemplate compiles prompts into editable scenes |
| AI model registry and generation tasks | AIImagePro | NanoBanana is a provider adapter; AIRealtimeImage is preview mode |
| Precision image editing | Titan Precision Edit contract | CreativeSuiteAnnotations + AdvancedImage |
| Commercial studio | Generalised FashionStudio | ProductPhotography is a quick recipe/Pebblely adapter |
| Structured documents and plans | Canvas-derived document engine | Existing Canvas UI/data patterns are adapted, not duplicated as authority |
| Assets and provenance | Titan Asset Fabric | ContentManager is a legacy input bridge |
| Opportunity-to-campaign orchestration | Titan Growth Foundry | BlogPilot scheduler/calendar/work-buffer logic may be reused |
| Publishing and social analytics | External SocialMedia adapter | Titan Create hands off approved assets and metadata |

No donor may retain duplicate authority for generation jobs, galleries, provider credentials, assets, project records, status polling, sharing, billing or credit calculation after migration.

## UI implementation rule

When changing an existing application surface:

1. Modify the closest existing CreativeSuite, FashionStudio, Canvas or campaign file.
2. When a new surface is genuinely necessary, duplicate the closest existing page/component first and adapt the duplicate.
3. Preserve shared layouts, components, spacing, typography, controls and interaction conventions.
4. Do not create unrelated visual systems, replacement dashboards or standalone styling.

## Delivery sequence

1. Compatibility baseline and contracts.
2. Security and tenant isolation.
3. Canonical assets, provider registry, jobs, usage and approvals.
4. Semantic scenes and precision editing.
5. Commercial Studio and vertical recipes.
6. Documents, plans and vertical packs.
7. Campaign Studio and Growth Foundry.
8. Optional video and UGC after the image/design platform is stable.

## Verification policy

Every capability must cite changed files and passing tests. Paid provider APIs are replaced by fake adapters in CI. Completion is not claimed without available lint, migration, unit, feature and build checks.
