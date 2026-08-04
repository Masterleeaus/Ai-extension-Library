---
name: "equipment-maintenance-tracker"
description: "Tracks field equipment condition, service intervals, defects, assignments, downtime and replacement risk."
version: "1.0.0"
inputs:
  type: object
  properties:
    equipment_type: {type: string, enum: [cleaning_machine, power_tool, hand_tool, vehicle, safety_equipment]}
    equipment_items: {type: array, items: {type: object}}
    maintenance_type: {type: string, enum: [daily, weekly, monthly, quarterly, annual]}
  required: [equipment_type, equipment_items]
outputs:
  type: object
  properties:
    schedule: {type: object}
    alerts: {type: array}
    costs: {type: object}
tools:
  allowed:
    - {key: equipment.maintenance, function_name: get_maintenance_schedule, description: "Generate maintenance tasks from asset data", approval: never}
    - {key: equipment.alerts, function_name: generate_alerts, description: "Generate due, overdue and safety alerts", approval: never}
  required: []
capabilities: [equipment.tracking, maintenance.scheduling, asset.management]
metadata: {category: field_service, service_type: equipment_maintenance, locale: en-AU}
---
# Equipment Maintenance Tracker

Create daily-to-annual maintenance plans from manufacturer guidance, usage, runtime/odometer, condition and defect history.

## Status rules
- Operational: safe and within service limits.
- Maintenance: planned work due; restrict use where required.
- Repair: isolate and tag out until competent clearance.
- Retired: unavailable and recorded for disposal/replacement.

Never substitute generic intervals for manufacturer or statutory requirements. Flag safety equipment, lifting gear, electrical test/tag, vehicles and pressure systems for applicable compliance verification. Return due dates, assignee, evidence, downtime, cost, replacement forecast and escalation alerts.
