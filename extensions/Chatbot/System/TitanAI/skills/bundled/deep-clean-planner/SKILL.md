---
name: "deep-clean-planner"
description: "Creates room-by-room deep-clean plans, estimates labour and supplies, and prepares governed scheduling actions."
version: "1.0.0"
inputs:
  type: object
  properties:
    property_type: {type: string, enum: [residential, commercial, strata]}
    rooms: {type: array, items: {type: string}}
    additional_services: {type: array, items: {type: string}}
    frequency: {type: string, enum: [one_off, weekly, fortnightly, monthly, quarterly]}
    completion_deadline: {type: string, format: date}
    site_notes: {type: string}
  required: [property_type, rooms]
outputs:
  type: object
  properties:
    plan: {type: object}
    estimated_hours: {type: number}
    required_supplies: {type: array}
    pricing: {type: object}
tools:
  allowed:
    - {key: cleaning.estimate, function_name: estimate_cleaning, description: "Generate a configurable cleaning estimate", approval: never}
    - {key: cleaning.schedule, function_name: schedule_cleaning, description: "Schedule a cleaning service after confirmation", approval: conditional}
    - {key: cleaning.supplies, function_name: get_supplies_list, description: "Build a task-specific supplies list", approval: never}
  required: []
capabilities: [cleaning.estimate, cleaning.schedule, cleaning.supplies]
metadata:
  category: cleaning
  service_type: deep_clean
  locale: en-AU
  skill_contract_version: "1.0"
---
# Deep Clean Planner

Create a practical room-by-room plan for residential, commercial or strata sites.

## Workflow
1. Identify scope, access, hazards, sensitivities, pets, fragile surfaces and exclusions.
2. Build tasks by room with method, product class, equipment, duration and acceptance check.
3. Order work top-to-bottom and clean-to-dirty; prioritise kitchens and bathrooms where relevant.
4. Estimate labour using team-hours, travel/setup, supplies and optional services.
5. Produce an itemised estimate in AUD, clearly marked as configurable and subject to site inspection.
6. Ask for explicit confirmation before invoking scheduling.

## Safety and quality
- Never recommend mixing bleach with acids, vinegar, ammonia or unknown products.
- Follow product labels, SDS requirements, ventilation and PPE controls.
- Escalate mould, biohazard, sharps, asbestos suspicion, sewage or structural damage.
- Use surface-compatible products and patch-test uncertain materials.
- Include a final inspection checklist and customer sign-off points.

## Output
Return: executive summary, assumptions, room plan, schedule, supplies, itemised estimate, risks, exclusions and quality checklist.
