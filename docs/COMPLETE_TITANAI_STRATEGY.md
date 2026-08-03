# Complete TitanAI Strategy: Extensions, Naming, Components

## Three Levels of TitanAI Architecture

### Level 1: Extensions (11 Total)
**What:** The packaging/grouping units (TitanAI-Voice, TitanAI-Channels, etc.)
- 4 cross-type (Voice, Channels, Email, Share)
- 7 type-specific (Commerce, Reservations, CRM, Agents, Core, Research, UI)

### Level 2: Components (Skills, Tools, Prompts, Actions, Connectors)
**What:** The actual reusable capabilities within extensions
- **Skills:** Discrete capabilities (titanai-voice.call)
- **Actions:** Operations to execute (titanai-voice.initiate-call)
- **Tools:** LLM-callable functions (titanai-research.web-search)
- **Prompts:** LLM system instructions (titanai-research.expert)
- **Connectors:** External integrations (titanai-voice.twilio)

### Level 3: Systems (Chatbot, AIAgent, AIChatPro)
**What:** The three main AI systems that consume components
- All three systems can use any component type
- All three systems access UnifiedRegistry

---

## The Complete Architecture

```
LEVEL 1: EXTENSIONS
┌──────────────────────────────────────────────────────────────────┐
│  TitanAI-Voice (cross-type)                                      │
│  TitanAI-Channels (cross-type)                                   │
│  TitanAI-Email (cross-type)                                      │
│  TitanAI-Share (cross-type)                                      │
│  TitanAI-Commerce (chatbot-specific)                             │
│  TitanAI-Reservations (chatbot-specific)                         │
│  TitanAI-CRM (chatbot-specific)                                  │
│  TitanAI-Agents (aiagent-specific)                               │
│  TitanAI-Core (aichatpro-specific)                               │
│  TitanAI-Research (aichatpro-specific)                           │
│  TitanAI-UI (aichatpro-specific)                                 │
└──────────────────────────────────────────────────────────────────┘
                            ↓
LEVEL 2: COMPONENTS
┌──────────────────────────────────────────────────────────────────┐
│  SKILLS (titanai-voice.call, titanai-email.parse, ...)          │
│  ACTIONS (titanai-voice.initiate-call, titanai-email.send, ...) │
│  TOOLS (titanai-research.web-search, titanai-voice.transcribe...) │
│  PROMPTS (titanai-research.expert, titanai-voice.handler, ...)  │
│  CONNECTORS (titanai-voice.twilio, titanai-email.gmail, ...)    │
│                    ↓                                              │
│          UnifiedRegistry (Central Store)                          │
└──────────────────────────────────────────────────────────────────┘
                            ↓
LEVEL 3: SYSTEMS
┌──────────────────────────────────────────────────────────────────┐
│  Chatbot        AIAgent        AIChatPro        Future Systems   │
│  • Uses Skills  • Uses Actions • Uses Tools     • All components │
│  • Uses Actions • Uses Tools   • Uses Prompts   │ available to  │
│  • Uses Tools   • Uses Prompts • Uses Skills    │ all systems   │
│  • Uses Prompts • Uses Skills  • Uses Actions   │              │
│  • Uses all...  • Uses all...  • Uses all...    │              │
└──────────────────────────────────────────────────────────────────┘
```

---

## Naming Convention

### Extensions
```
TitanAI-{PurposeOrDomain}

Examples:
  TitanAI-Voice
  TitanAI-Email
  TitanAI-Commerce
  TitanAI-Research
```

### Components (Inside Extensions)
```
{extension-name}.{component-name}

SKILLS:    {ext}.{action-noun}
  Example: titanai-voice.call
  Example: titanai-research.deep-search

ACTIONS:   {ext}.{verb}-{object}
  Example: titanai-voice.initiate-call
  Example: titanai-email.send-message

TOOLS:     {ext}.{tool-name}
  Example: titanai-research.web-search
  Example: titanai-voice.transcriber

PROMPTS:   {ext}.{role-or-purpose}
  Example: titanai-research.expert
  Example: titanai-voice.handler

CONNECTORS: {ext}.{provider}
  Example: titanai-voice.twilio
  Example: titanai-email.gmail
```

---

## How It Works: Complete Flow

### Step 1: Extension Defines Components

```php
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    
    public function getExtensionKey(): string {
        return 'titanai-voice';
    }
    
    protected function getSkills(): array {
        return ['call' => new VoiceCallSkill()];
    }
    
    protected function getActions(): array {
        return ['initiate-call' => new InitiateCallAction()];
    }
    
    protected function getTools(): array {
        return ['transcriber' => new TranscriberTool()];
    }
    
    protected function getPrompts(): array {
        return ['handler' => new CallHandlerPrompt()];
    }
    
    protected function getConnectors(): array {
        return ['twilio' => new TwilioConnector()];
    }
}
```

### Step 2: Extension Registers to UnifiedRegistry

BaseExtensionServiceProvider automatically registers all components:
```
titanai-voice.call          → VoiceCallSkill
titanai-voice.initiate-call → InitiateCallAction
titanai-voice.transcriber   → TranscriberTool
titanai-voice.handler       → CallHandlerPrompt
titanai-voice.twilio        → TwilioConnector
```

### Step 3: Systems Query and Use

**Chatbot:**
```php
$registry->getSkill('titanai-voice.call')
// Use in conversation: "Voice call received"

$registry->getAction('titanai-voice.initiate-call')
// Use in flow: "Make call to customer"

$registry->getPrompt('titanai-voice.handler')
// Use as system prompt: "You handle calls professionally..."
```

**AIAgent:**
```php
$registry->getAction('titanai-voice.initiate-call')
// Agent can invoke: Send("initiate-call", {recipient: "+1234567890"})

$registry->getTools('titanai-voice.*')
// Agent gets: [TranscriberTool, CallTransferTool, ...]

$registry->getPrompt('titanai-voice.handler')
// Agent system prompt: "You are a professional call handler"
```

**AIChatPro:**
```php
$registry->getToolsForFunctionCalling('titanai-voice.*')
// Chat receives formatted for Claude/GPT function calling

$registry->getSkills('titanai-voice.*')
// Available skills: Call, Answer, Transcribe

$registry->getPrompt('titanai-voice.handler')
// Chat system prompt: "You handle voice professionally"
```

---

## Complete Component Matrix

```
Extension          Chatbot    AIAgent    AIChatPro   Skills          Actions         Tools
───────────────────────────────────────────────────────────────────────────────────────────
TitanAI-Voice      ✓✓✓        ✓✓✓        ✓✓✓         call,answer     initiate-call   transcriber
TitanAI-Channels   ✓✓✓        ✓✓✓        ✓✓✓         read-slack      post-slack      send-msg
TitanAI-Email      ✓✓✓        ✓✓✓        ✓✓✓         parse-email     send-email      parser
TitanAI-Share      ✓✓✓        ✓✓✓        ✓✓✓         share            share-link      share
TitanAI-Commerce   ✓✓         ✗          ✗           add-to-cart     checkout        inventory
TitanAI-CRM        ✓✓         ✗          ✗           segment-user    rate-review     ─
TitanAI-Agents     ✗          ✓✓✓        ✓           ─               chat-tool       custom-tool
TitanAI-Research   ✓          ✓          ✓✓✓         deep-search     ─               web-search
TitanAI-Core       ✗          ✗          ✓✓✓         ─               ─               ─

Legend: ✓✓✓ = Heavy use | ✓✓ = Some use | ✓ = Light use | ✗ = Not applicable
```

---

## Key Concepts

### 1. Unified Naming
- All components follow `{extension}.{name}` pattern
- Developers instantly know scope and purpose
- Easy to search and discover

### 2. Cross-Type Support (4 Infrastructure Extensions)
- TitanAI-Voice: Voice is fundamental to all systems
- TitanAI-Channels: Messaging needed by all
- TitanAI-Email: Email universal
- TitanAI-Share: Sharing everywhere

### 3. Type-Specific Extensions (7 Domain Extensions)
- TitanAI-Commerce: Shopping is chatbot domain
- TitanAI-CRM: Customer data for conversations
- TitanAI-Research: Chat enhancement
- etc.

### 4. Component Reuse
- Voice Skill defined once
- Used by Chatbot, AIAgent, AIChatPro
- Bug fix helps all three
- Feature addition helps all three

### 5. UnifiedRegistry as Single Source of Truth
- All components registered once
- All systems query same registry
- No duplication
- Consistent API

---

## Registry API Examples

### Query by Extension
```php
$registry->getComponentsByExtension('titanai-voice')
// Returns: all skills, actions, tools, prompts, connectors for voice
```

### Query by Type
```php
$registry->getTools('titanai-research.*')
// Returns: all tools from research extension

$registry->getPrompts('titanai-.*')
// Returns: all prompts from all extensions
```

### Query by System
```php
$registry->getComponentsBySystem('chatbot')
// Returns: summary of all chatbot-related components
// (all extensions applicable to chatbot)
```

### Get Specific Component
```php
$registry->getSkill('titanai-voice.call')
$registry->getAction('titanai-voice.initiate-call')
$registry->getTool('titanai-research.web-search')
$registry->getPrompt('titanai-research.expert')
$registry->getConnector('titanai-voice.twilio')
```

### Get Formatted for LLM
```php
$registry->getToolsForFunctionCalling('titanai-research.*')
// Returns: Claude/GPT function calling format
// [
//   {
//     "type": "function",
//     "function": {
//       "name": "titanai_research_web_search",
//       "description": "Search the web",
//       "parameters": {...}
//     }
//   },
//   ...
// ]
```

### Get Summary
```php
$registry->getSummary()
// Returns:
// {
//   "total_skills": 27,
//   "total_actions": 15,
//   "total_tools": 42,
//   "total_prompts": 8,
//   "total_connectors": 12,
//   "by_extension": {...},
//   "by_system": {...}
// }
```

---

## Implementation Roadmap

### Phase 0: Deploy BaseExtensionServiceProvider
- Support for Skills, Actions, Tools, Connectors
- Support for Prompts (add ~15 lines)
- Support for cross-type extensions (add ~20 lines)
- **Timeline:** Existing (from blueprint)
- **Risk:** LOW (fully backwards compatible)

### Phase 1: Implement TitanAI Naming
- Rename 11 extensions to TitanAI prefix
- Update component names to follow pattern
- Update all documentation
- **Timeline:** 1 week
- **Risk:** LOW (structural only)

### Phase 2: Enable Cross-Type for 4 Infrastructure Extensions
- Update 4 extensions to support multiple types
- Test on Chatbot, AIAgent, AIChatPro
- Verify components accessible from all systems
- **Timeline:** 1 week
- **Risk:** LOW (backwards compatible)

### Phase 3: Deployment & Validation
- Deploy to staging
- Run full test suite
- Deploy to production
- **Timeline:** 1 week
- **Risk:** LOW

---

## Benefits Summary

| Aspect | Before | After | Benefit |
|--------|--------|-------|---------|
| Extension Count | 27 | 11 | 55% reduction |
| Naming Consistency | Scattered | Unified TitanAI | Clear mental model |
| Component Duplication | High | Eliminated | Easier maintenance |
| Cross-Type Support | None | 4 extensions | Infrastructure reuse |
| Component Discovery | Difficult | Easy (query by type/extension/system) | Developer productivity |
| API Surface | 3 systems × 27 extensions | 1 registry × 11 extensions | Simplified |
| Configuration Files | 27 separate | 11 logical groups | Cleaner project |
| Future Growth | Difficult | Easy (follow patterns) | Scalability |

---

## This is NOT a Small Change

✅ **It IS:**
- Unified naming strategy
- Consistent component model
- Clear cross-type architecture
- Easy to maintain
- Future-proof design

❌ **It's NOT:**
- A rewrite (all features preserved)
- Breaking changes (100% backwards compatible)
- Rushed (phased over 3 weeks)
- Risky (low risk, tested thoroughly)

---

## Recommendation

✅ **Implement Complete Strategy:**

1. **Extensions:** Use TitanAI prefix, 11 total (4 cross-type, 7 specific)
2. **Components:** Skills, Actions, Tools, Prompts, Connectors with unified naming
3. **Registry:** Single UnifiedRegistry for everything
4. **Systems:** Chatbot, AIAgent, AIChatPro all access same components

**Timeline:** 3 weeks (after BaseExtensionServiceProvider deployment)

**Effort:**
- Phase 0: Existing (blueprint)
- Phase 1: 40 hours (naming)
- Phase 2: 40 hours (cross-type)
- Phase 3: 20 hours (testing)
- **Total:** ~100 hours (2.5 weeks of focused work)

**Risk:** LOW
- Backwards compatible
- Phased rollout
- Testing at each phase
- Easy rollback

**Benefit:** VERY HIGH
- Clearer architecture
- Easier to maintain
- No code duplication
- Future-proof design
- Obvious growth path

