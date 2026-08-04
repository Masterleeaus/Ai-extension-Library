# Titan Zero — AIChatPro, Chatbot/PWA and AI Agents Deep Scan

## Executive summary

This report analyses the three official AI extension families as one cooperating Titan Zero system:

- **AIChatPro** — internal/system AI workspace
- **Chatbot/PWA** — external, customer and field-worker interaction surface
- **AI Agents** — shared execution, workflow and automation runtime

The archives contain:

- **20 AIChatPro extensions**
- **12 Chatbot/PWA extensions**
- **9 AI Agent extensions**
- **41 official extensions total**
- **1,064 extracted files**
- **893 PHP files**
- Approximately **110,872 lines of PHP**
- All PHP files pass syntax validation
- No genuine automated test suite was found

The systems should not be treated as competing products. The correct architecture is:

```text
AIChatPro       = where the business thinks, investigates and controls
Chatbot/PWA     = where customers and field workers interact
AI Agents       = how both systems execute work
Chatbot Agent   = human support and escalation inbox
```

## Principal findings

1. **AIChatPro is the correct Titan Zero system AI.** It already provides connectors, tools, deep research, file chat, memory, skills, Canvas, folders, sharing, entity highlighting and Vision/PDF categories.
2. **Chatbot is a substantial external/PWA foundation**, not merely a website widget. It contains customers, conversations, attachments, knowledge, embeddings, analytics, tickets, channels and human handover.
3. **AI Agent is a genuine workflow runtime** with triggers, actions, delayed jobs, memory, knowledge sources, tool calling, conversations and multi-channel execution.
4. **Agent → Chatbot integration already exists** through `ai-agent-tool-chatbot`.
5. **A native AIChatPro → AI Agent bridge is missing** and is the highest-priority integration gap.
6. **Chatbot Agent and AI Agent are different systems:** Chatbot Agent is the human inbox; AI Agent is autonomous execution.
7. **The supplied `ai-agent-outlook` archive is mispackaged.** Its namespace, models and registry all belong to AIChatPro Outlook, not AI Agent.
8. **AIChatPro Smart Image is web image search**, not uploaded-photo inspection.
9. **AIChatPro core already recognises `ai_vision`**, making it the correct uploaded-image reasoning entry point.
10. **Connector credentials and permissions are duplicated** and should be unified behind an encrypted Titan Connector Vault.

---

# 1. AIChatPro — internal system AI

AIChatPro should be the internal workspace for owners, managers, dispatchers, office staff, compliance teams and senior technicians.

It can become the main interface for:

- Querying WorkCore customers, jobs, quotes, invoices and assets
- Reviewing and approving agent actions
- Researching regulations and tenders
- Reading connected email, calendar, Drive and Notion content
- Producing and editing reports, SOPs, proposals and responses
- Managing business memory and reusable skills
- Escalating difficult technical questions

## AIChatPro extension inventory

| Extension | Actual capability | Titan value |
|---|---|---|
| AIChatPro | Internal chat shell, connector registry, policies and tool service | Core system AI |
| Deep Research | OpenAI/Gemini research sessions and Canvas output | Sentinel, Tender and Opportunity |
| Entity Highlight | Clickable entity detection and detail drawer | Customers, assets, companies and regulations |
| File Chat | File-grounded conversation | Tenders, manuals, contracts and evidence |
| Folders | Conversation organisation | Projects and clients |
| Gmail | Search/read Gmail | Internal context |
| Google Calendar | Upcoming/find events | Scheduling context |
| Google Drive | Search/read Drive files | Document context |
| Highlight to Ask | Select text and ask follow-ups | Fast investigation |
| Memory | Stored user instructions/context | Titan memory foundation |
| Notion | Search/read Notion pages | Knowledge context |
| Outlook | Search/read Outlook | Internal context |
| Skills | Reusable skills, parser and tool service | Titan Skill Library foundation |
| Smart Image | Search Serper/Perplexity/OpenAI for web images | Reference imagery only |
| Canvas | Tiptap document editing | Reports, SOPs and proposals |
| Temporary Chat | Non-persistent chat mode | Privacy-sensitive work |
| Chat Setting | Categories, templates and trained assistants | Custom internal assistants |
| Chat Share | Shareable conversations | Collaboration |
| Voice Isolator | Audio cleanup integration | Field voice utility |
| Webchat | Search-supported AI web chat | Research surface |

## Smart Image correction

Smart Image registers a tool named `search_images`. It retrieves external image URLs through Serper, Perplexity, OpenAI web search and Open Graph metadata. It does not:

- Inspect uploaded job photos
- Detect defects or damage
- Compare before and after images
- Identify hazards or PPE
- Read equipment plates
- Perform visual estimating

The correct Titan visual stack is:

```text
AIChatPro Vision AI       → uploaded-image reasoning
Creative Annotations      → bounding boxes and overlays
Smart Image               → external reference imagery
Owning Titan app          → domain decision and workflow
```

---

# 2. Chatbot/PWA — customer and field interaction

The Chatbot core includes:

- Chatbots and channels
- Customers
- Conversations and histories
- Attachments and media
- Knowledge-base articles
- Embeddings and training sources
- OpenAI, Anthropic and Gemini generators
- PDF, text, Excel and URL parsers
- Canned responses
- Analytics and page visits
- Ticket statuses
- Human-agent conditions
- Multi-channel webhooks
- PWA/API routes

## Chatbot extension inventory

| Extension | Actual capability | Titan value |
|---|---|---|
| Chatbot | External/PWA chatbot platform | Core customer/field interface |
| Chatbot Agent | Human support panel, pin/close and realtime events | Human handover |
| Booking | Calendly booking | Simple booking donor |
| Customer Tag | Tags and synchronisation | Segmentation and routing |
| Ecommerce | Cart plus WooCommerce/Shopify tools | Parts, plans and commerce |
| Instagram | OAuth, signed webhooks and DM processing | Omnichannel support |
| Messenger | Messenger webhook and conversations | Omnichannel support |
| Review | Review/feedback configuration | Titan Proof/Reputation |
| Telegram | Telegram channel | Customer/team channel |
| Voice | Voice chatbot, training and histories | Spoken PWA assistant |
| Voice Call | Call settings/controller | Telephony bridge |
| WhatsApp | Twilio WhatsApp conversations | Primary service channel |

## Titan PWA roles

### Customer

- Request jobs
- Upload photos
- Book service
- Receive and approve quotes
- Approve variations
- Pay invoices
- Review work
- Access warranties and asset records

### Field worker

- View next job
- Ask Field Expert
- Capture evidence
- Complete SafeWork checks
- Record parts and variations
- Finish work and collect sign-off

The generic conversation surface should be extended with WorkCore context: tenant, customer, property, job, worker, quote, invoice, asset, safety plan and evidence set.

---

# 3. AI Agents — shared execution plane

The AI Agent core contains:

- Workflow definitions and runs
- Action registry and dispatcher
- AI, message, memory, path and nested-workflow actions
- Schedule, channel-message and webhook triggers
- Delayed execution jobs
- Knowledge sources
- Workflow Copilot
- Tool calling
- Internal MagicAI channel
- Telegram connector
- Conversations and messages
- Plan limits and reporting

## AI Agent extension inventory

| Extension | Actual capability | Titan value |
|---|---|---|
| AI Agent | Workflow, triggers, actions, memory and knowledge | Core automation runtime |
| Gmail | Search, send, reply, drafts, labels, archive, delete, attachments | Full email automation |
| Outlook | Actually AIChatPro Outlook search/read connector | Mispackaged; replace |
| Slack Channel | Slack connector and webhook | Team channel |
| Tool: Chatbot | Chatbot listing, conversations, analytics and ticket closure | Existing Agent→Chatbot bridge |
| Tool: Marketing Bot | Campaign actions and reports | Marketing automation |
| Tool: Social Media | Create/approve posts and analytics | Social automation |
| WhatsApp Channel | Meta WhatsApp connector | Agent channel |
| Phone Call Agent | Twilio/ElevenLabs agents, calls, transcripts and training | Voice receptionist and collections |

## Existing Agent → Chatbot bridge

`ai-agent-tool-chatbot` registers an `external_chatbot` action and provides tools for:

- Listing chatbots
- Listing/getting conversations
- Retrieving analytics
- Closing tickets
- Enriching reports with Chatbot data

This confirms that AI Agents are intended to operate across Chatbot conversations.

## Missing AIChatPro → Agent bridge

AIChatPro has a connector/tool registry, but no extension registers Agent workflows as native AIChatPro tools.

Recommended bridge capabilities:

```text
agent.list
agent.get
agent.run
agent.create
agent.update
agent.pause
agent.resume
agent.run_status
agent.workflow_runs
agent.pending_approvals
agent.approve
agent.reject
```

Agents should return progress, approval cards, generated documents, exceptions and final results directly into AIChatPro conversations.

---

# 4. Phone Call Agent

Phone Call Agent is one of the strongest packages in the stack. It includes:

- Twilio and ElevenLabs providers
- Per-agent credentials with global fallback
- Inbound call webhooks
- Twilio WebSocket relay
- LLM integration
- Training from text, files and URLs
- Calls, transcripts and tags
- PDF export
- Searchable history
- ElevenLabs web simulation
- Usage charging

## Titan uses

- Titan Pay invoice follow-up
- Smart Dispatch emergency intake
- Quote rescue
- Customer booking
- Maintenance reminders
- After-hours receptionist
- Promise-to-pay capture

Its URL parser uses `file_get_contents()` and requires SSRF protection, size limits, redirect controls, timeouts and domain policies.

---

# 5. Connector consolidation

AIChatPro connectors are mostly read-only while Agent connectors can perform actions. That permission distinction is useful, but customers should not connect the same account twice.

## Recommended Titan Connector Vault

One encrypted connector record should expose capability grants:

| Surface | Default access |
|---|---|
| AIChatPro | Search/read |
| Chatbot/PWA | Conversation-specific only |
| Agent draft mode | Propose drafts/actions |
| Approved Agent | Execute within policy |
| Administrator | Configure, audit and revoke |

The vault should serve Gmail, Outlook, Calendar, Drive, Notion, Slack, Telegram, WhatsApp, Instagram, Messenger, Twilio and ElevenLabs.

Each capability should declare:

- Input/output schema
- Required role
- Read/write scope
- Approval requirement
- Tenant and user ownership
- External provider
- Privacy classification
- Audit and retention requirements

---

# 6. Mapping to the top ten premium apps

| App | AIChatPro | Chatbot/PWA | AI Agents |
|---|---|---|---|
| Titan Pay | Policies and collection review | Payment portal and replies | Deposits, invoices and dunning |
| Titan Quote | Estimate and margin workspace | Quote/variation approval | Rescue and follow-up |
| Titan Sentinel | Research and impact approval | Worker alerts | Monitoring and corrective workflows |
| Titan Evidence | Evidence review | Camera and acknowledgement | Missing-evidence checks and pack assembly |
| Titan Field Expert | Senior expert workspace | Mobile visual/voice helper | Retrieval and escalation |
| Titan SafeWork | SOP governance | Field pre-starts and SWMS | Hazard/weather/incident triggers |
| Titan Smart Dispatch | Dispatch console | Intake and worker UI | Qualification and assignment |
| Titan Award | Payroll review | Time/travel capture | Scheduled compliance checks |
| Titan Tender | Research/response workspace | Mobile approvals | Portal monitoring and extraction |
| Titan Asset | Portfolio oversight | Plate capture/service UI | Maintenance and warranty workflows |

---

# 7. Security and production findings

## Positive controls

- AI Agent defines policies for workflows, channels and memory.
- Many Agent controllers scope records by authenticated user.
- Agent Chatbot tools generally scope records to workflow owner.
- Instagram implements HMAC webhook signature validation.
- AIChatPro connectors include policies and token-invalidated events.

## Required remediation

1. **Add automated tests** for tenancy, connectors, agent idempotency, approvals, webhooks, handover and retries.
2. **Unify duplicate connector credentials** and revocation.
3. **Harden URL ingestion** in Phone Call Agent and other training parsers against SSRF.
4. **Replace destructive GET routes** with CSRF-protected state-changing methods.
5. **Add signed generic webhooks**, replay protection and timestamp validation.
6. **Standardise verification** across WhatsApp, Messenger, Slack, Telegram, Twilio and ElevenLabs.
7. **Replace global secrets** with tenant/user/device BYO credentials and encrypted vault storage.
8. **Unify memory scopes** across personal, business, customer, job, property, asset and agent memory.
9. **Add shared conversation correlation IDs** across chat, PWA, voice and agents.
10. **Complete extension uninstall/data-retention behaviour.**

---

# 8. Recommended implementation order

## Phase 1 — unify the systems

1. Build AIChatPro ↔ AI Agent bridge.
2. Create Titan Connector Vault.
3. Create shared Capability Registry.
4. Standardise approval requests and results.
5. Add common conversation correlation IDs.
6. Replace the mispackaged Agent Outlook extension.
7. Consolidate voice and phone channel ownership.

## Phase 2 — business context

8. Add WorkCore context provider.
9. Add scoped memory architecture.
10. Attach every tool/action to its owning premium app.
11. Inject customer, property, job, quote, invoice and asset context.
12. Add privacy classification and local/BYO/Titan AI routing.

## Phase 3 — field and customer experience

13. Turn Chatbot into the stable PWA shell.
14. Add the shared camera/Vision bridge.
15. Add human handover and approval cards.
16. Add offline queue and conflict handling.
17. Add role-specific customer, cleaner, technician and manager interfaces.

## Final conclusion

The official extensions provide a strong base for Titan Zero’s intelligence system. The priority is not replacing them, but connecting them through shared identity, permissions, connectors, context, memory, approvals and WorkCore domain capabilities.

> **AIChatPro is where the business thinks and controls. Chatbot/PWA is where customers and field workers interact. AI Agents are how both systems take action.**
