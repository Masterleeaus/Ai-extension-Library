---
name: "maintenance-scheduler"
description: "Creates recurring preventive-maintenance schedules for residential, commercial and strata properties."
version: "1.0.0"
inputs:
  type: object
  properties:
    property_type: {type: string, enum: [residential, commercial, strata]}
    maintenance_areas: {type: array, items: {type: string, enum: [roof, gutters, plumbing, electrical, hvac, fencing, painting, flooring, fire_safety, grounds]}}
    frequency: {type: string, enum: [monthly, quarterly, biannual, annual]}
    climate_zone: {type: string}
  required: [property_type, maintenance_areas]
outputs:
  type: object
  properties:
    schedule: {type: object}
    estimated_costs: {type: object}
    checklist: {type: array}
tools:
  allowed:
    - {key: maintenance.schedule, function_name: generate_schedule, description: "Generate a recurring maintenance schedule", approval: never}
    - {key: maintenance.checklist, function_name: get_checklist, description: "Generate inspection and service checklists", approval: never}
  required: []
capabilities: [maintenance.scheduling, property.maintenance, seasonal.checklist]
metadata: {category: handyman, service_type: maintenance_scheduling, locale: en-AU}
---
# Maintenance Scheduler

Build preventive schedules based on asset age, manufacturer intervals, climate, occupancy, statutory inspections and previous defects.

Use Australian seasons. Distinguish owner checks from licensed service work. Prioritise life safety, water ingress, electrical/gas hazards, structural defects and security. Include monthly budget smoothing, responsible party, evidence required, due date, escalation threshold and completion record.

Never invent a statutory interval. Mark jurisdiction-dependent compliance items for verification.
