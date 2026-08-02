---
name: "handyman-quote-generator"
description: "Builds itemised handyman estimates with scope, labour, materials, exclusions, variations and licensing checks."
version: "1.0.0"
inputs:
  type: object
  properties:
    job_type: {type: string, enum: [repair, installation, maintenance, renovation, emergency]}
    job_items: {type: array, items: {type: string}}
    property_type: {type: string, enum: [residential, commercial, strata]}
    urgency: {type: string, enum: [standard, urgent, emergency]}
    location: {type: string}
  required: [job_type, job_items]
outputs:
  type: object
  properties:
    quote: {type: object}
    estimated_time: {type: string}
    total_price: {type: object}
    materials_list: {type: array}
tools:
  allowed:
    - {key: handyman.quote, function_name: generate_handyman_quote, description: "Generate an itemised handyman estimate", approval: never}
    - {key: handyman.materials, function_name: get_materials_list, description: "Create a materials and consumables list", approval: never}
  required: []
capabilities: [handyman.quote, repairs.estimate, maintenance.planning]
metadata: {category: handyman, service_type: quote_generation, locale: en-AU}
---
# Handyman Quote Generator

Create clear estimates in AUD with labour, materials, markup, travel, disposal, GST treatment, contingencies and validity period.

## Mandatory checks
- Identify work requiring a licensed electrician, plumber, gasfitter, builder, engineer or other regulated trade in the relevant Australian jurisdiction.
- Do not scope unlicensed electrical, gas, major plumbing, structural, waterproofing or asbestos work as ordinary handyman work.
- Separate assumptions, exclusions and provisional sums.
- Require written approval for variations and customer confirmation before booking.

Output client/job summary, scope, materials, labour assumptions, timeline, itemised estimate, payment terms, warranty assumptions, compliance referrals and variation process.
