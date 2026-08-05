# Chatbot Vertical Templates — Pass 1

## Scope

This pass adds Titan Zero's nine vertical families as composable presets inside the existing Chatbot PWA shell. It does not create separate PWAs, databases, outboxes or sync engines.

## Platform applications

Vertical presets can configure the same shared customer and business surfaces:

- Titan Zero
- Titan Go
- Titan Desk
- Titan Hub

## WorkCore workspace overlays

Every vertical can compose the first four WorkCore workspace templates:

- CRM
- Projects and Jobs
- Finance
- Crew and Team

These are presentation and capability overlays. WorkCore remains authoritative for operational records and governed actions.

## Vertical families

1. Field and Home Services
2. BnB, Hotel and Rooming Services
3. Real Estate
4. Salons and Personal Care
5. Fitness and Membership Businesses
6. Automotive Services
7. E-commerce and Retail
8. Hire and Rental
9. Booking, Reservation and Capacity-Based Businesses

Facilities maintenance and facilities contractors are included inside Field and Home Services rather than being registered as a separate tenth vertical.

## Template contract

Each vertical declares:

- covered business segments;
- supported platform applications;
- enabled WorkCore workspaces;
- role presets;
- functional features;
- commerce modes;
- WorkCore domains, commands and read models;
- offline records, packs and conflict policy;
- chatbot role, system prompt and suggested prompts;
- shell navigation, home widgets and permissions.

## Offline boundary

Vertical templates reuse the existing Chatbot edge runtime and shared outbox. Offline requests may be drafted and queued, but scarce-resource bookings, authoritative inventory, payments, refunds and compliance approvals remain pending until the authoritative service confirms them.

## Compatibility repair

The pass also resolves committed merge markers in the Titan catalogue controller and template schema path, while retaining the split between five canonical platform applications and reusable vertical/workspace templates.

## Next passes

1. Add vertical-specific terminology overlays.
2. Add role-specific navigation presets.
3. Add detailed WorkCore command/read-model allowlists per vertical.
4. Add Ecommerce catalogue, booking, hire and checkout overlays.
5. Add vertical onboarding and template preview UI.
6. Add offline pack manifests and sync projections.
