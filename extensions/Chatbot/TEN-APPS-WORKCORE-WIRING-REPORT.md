# Ten Titan Apps → WorkCore Wiring

All ten app templates are included. WorkCore remains authoritative; offline writes are provisional and use the chatbot outbox.

| App | Domain | Reads | Actions | Offline stores |
|---|---|---:|---:|---|
| Titan Hub | customer-portal | 6 | 4 | customers, properties, jobs, quotes, invoices, knowledge, conversations |
| Titan Go | field-operations | 6 | 8 | assigned_jobs, customers, properties, checklists, time_entries, attachments, incidents, materials |
| Titan Dispatch | dispatch | 5 | 5 | dispatch_board, jobs, workers, availability, routes, incidents |
| Titan Front Desk | reception | 5 | 6 | customers, properties, availability_snapshots, bookings, callbacks, conversations |
| Titan Money | finance | 6 | 7 | quotes, invoices, invoice_items, payment_status, expenses, pricing_rules |
| Titan Teams | workforce | 5 | 5 | workers, teams, availability, timesheets, leave, team_conversations |
| Titan Locker | inventory | 5 | 5 | inventory_items, stock_snapshots, equipment, materials, usage, reorder_requests |
| Titan Marketing | marketing | 5 | 4 | customer_segments, campaign_drafts, templates, services, reviews |
| Titan Social | social | 4 | 4 | social_accounts, post_drafts, content_calendar, templates, queued_replies |
| Titan Analytics | analytics | 5 | 3 | metric_snapshots, reports, dashboard_specs, alerts |

Execution path:

`Titan app / Generative UI → GovernedToolExecutor → WorkCoreAppBridge::executeForApp → WorkCoreRuntimeClient → AiToolRouter → WorkCore read model or business action`

The app bridge rejects tools not assigned to the requesting Titan app. High-risk actions require governance confirmation.
