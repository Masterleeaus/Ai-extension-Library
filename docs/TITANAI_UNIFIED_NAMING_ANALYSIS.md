# TitanAI Unified Naming & Cross-Type Architecture

## Current Problem: Type-Based Naming Hides Unified Purpose

```
Current (Type-Specific):
  ChatbotVoiceIntegration   ← Only works with Chatbot
  AIAgentChannels            ← Only works with AIAgent
  AIChatProCore              ← Only works with AIChatPro

Issue: Naming suggests these are siloed by type, but conceptually:
  - Voice should work everywhere (Chatbot, AIAgent, users of chatbot)
  - Channels are integration points (AIAgent uses them, but Chatbot could too)
  - Core features might be useful across systems
```

## Proposal: TitanAI Unified Naming

```
Rename all extensions to TitanAI* prefix:
  
ChatbotVoiceIntegration    → TitanAI-Voice
ChatbotCommerce             → TitanAI-Commerce
ChatbotCustomerIntelligence → TitanAI-CRM
ChatbotBooking              → TitanAI-Reservations

AIAgentChannels             → TitanAI-Channels
AIAgentGmail                → TitanAI-Email
AIAgentTools                → TitanAI-Agents (or TitanAI-WorkerTools)

AIChatProCore               → TitanAI-Core
AIChatProAI                 → TitanAI-Research
AIChatProUX                 → TitanAI-UI
AIChatProCollaboration      → TitanAI-Share
```

## Question: Can Extensions Work On ALL Three Main Systems?

**Short Answer:** YES, technically! But needs architecture changes.

### Current Architecture (Type-Restricted)

```php
// Extension is tied to ONE parent system
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    public function getExtensionType(): string {
        return 'chatbot';  // ← Only this type
    }
}

// Config looks for this type only
config('titanai.extensions.chatbot.auto_register_to_unified_registry')
```

**Current Behavior:** TitanAI-Voice can ONLY work with Chatbot system

### Proposed: Cross-Type Architecture

```php
// Extension can work with MULTIPLE parent systems
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    public function getExtensionTypes(): array {
        return [
            'chatbot',      // Chatbot can use voice skills
            'aiagent',      // AIAgent can use voice actions
            'aichatpro',    // AIChatPro can use voice connectors
        ];
    }
    
    // Provide components for each type
    public function getSkills(): array {
        return [
            'voice-call' => new VoiceCallSkill(),     // For Chatbot
        ];
    }
    
    public function getActions(): array {
        return [
            'initiate-call' => new InitiateCallAction(),  // For AIAgent
        ];
    }
    
    public function getConnectors(): array {
        return [
            'twilio' => new TwilioConnector(),  // For AIChatPro
        ];
    }
}
```

---

## Analysis: Which Extensions Should Be Cross-Type?

### Type A: SHOULD Be Cross-Type (Infrastructure/Integration)

| Extension | Chatbot | AIAgent | AIChatPro | Reason |
|-----------|---------|---------|-----------|--------|
| **TitanAI-Voice** | ✓ Skills (call handling) | ✓ Actions (make call) | ✓ Connector (audio routing) | All systems might need audio |
| **TitanAI-Channels** | ✓ Skills (read Slack) | ✓ Actions (post to Slack) | ✓ Connector (sync Slack) | All systems use messaging |
| **TitanAI-Email** | ✓ Skills (read email) | ✓ Actions (send email) | ✓ Connector (Gmail sync) | Email is universal |
| **TitanAI-Share** | ✓ Skill (share response) | ✓ Action (share result) | ✓ Feature (built-in share) | Sharing everywhere |

**Pattern:** Infrastructure/integration extensions benefit from cross-type

### Type B: SHOULD Remain Type-Specific (Domain Features)

| Extension | Best Fit | Why |
|-----------|----------|-----|
| **TitanAI-Commerce** | Chatbot only | Shopping is chatbot-specific |
| **TitanAI-Reservations** | Chatbot only | Booking conversations |
| **TitanAI-CRM** | Chatbot only | Customer context for chatbot |
| **TitanAI-Research** | AIChatPro only | Deep research for chat enhancement |
| **TitanAI-UI** | AIChatPro only | UI layer specific to chat UI |
| **TitanAI-Core** | AIChatPro only | Foundation for AIChatPro only |

**Pattern:** Domain-specific extensions stay with their parent system

---

## Two Strategies

### Strategy A: Cross-Type Framework (Advanced)

**Modify BaseExtensionServiceProvider:**

```php
// Support multiple types
abstract class BaseExtensionServiceProvider extends ServiceProvider {
    
    // Can override for single type (current)
    public function getExtensionType(): string {
        return 'chatbot';
    }
    
    // Or override for multiple types (new)
    public function getExtensionTypes(): array {
        return [$this->getExtensionType()];  // Default: single type
    }
    
    public function boot(): void {
        foreach ($this->getExtensionTypes() as $type) {
            // Register components for this type
            config("titanai.extensions.{$type}.auto_register_to_unified_registry")
            // ... register appropriately
        }
    }
}
```

**Example: TitanAI-Voice (Cross-Type)**

```php
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    public function getExtensionKey(): string {
        return 'titanai-voice';
    }
    
    // Works on all three systems
    public function getExtensionTypes(): array {
        return ['chatbot', 'aiagent', 'aichatpro'];
    }
    
    // Skills for Chatbot
    public function getSkills(): array {
        return [
            'handle-voice-call' => new VoiceCallSkill(),
        ];
    }
    
    // Actions for AIAgent
    public function getActions(): array {
        return [
            'initiate-call' => new InitiateCallAction(),
            'transfer-call' => new TransferCallAction(),
        ];
    }
    
    // Connectors for AIChatPro
    public function getConnectors(): array {
        return [
            'twilio' => new TwilioConnector(),
        ];
    }
}
```

### Strategy B: Keep Type-Specific (Simpler, Current)

**Don't change architecture, just rename:**

```
TitanAI-Voice-Chatbot       ← For Chatbot type
TitanAI-Channels-AIAgent    ← For AIAgent type
TitanAI-Core-AIChatPro      ← For AIChatPro type
```

**Pro:** No architecture changes, simple  
**Con:** Verbose naming, no cross-type benefit

---

## Recommended Approach: Hybrid

### Unified Naming with Optional Cross-Type

```
CROSS-TYPE Extensions (Work on 2+ systems):
  1. TitanAI-Voice
  2. TitanAI-Channels
  3. TitanAI-Email
  4. TitanAI-Share

TYPE-SPECIFIC Extensions (Work on 1 system):
  5. TitanAI-Commerce (Chatbot only)
  6. TitanAI-Reservations (Chatbot only)
  7. TitanAI-CRM (Chatbot only)
  8. TitanAI-Agents (AIAgent only)
  9. TitanAI-Core (AIChatPro only)
  10. TitanAI-Research (AIChatPro only)
  11. TitanAI-UI (AIChatPro only)
```

**Benefits:**
- ✓ Unified naming (TitanAI prefix)
- ✓ Cross-type where it makes sense
- ✓ Type-specific where needed
- ✓ Clear intent (4 cross-type, 7 specific)

---

## Full Refined Extension List (With New Names)

### CROSS-TYPE EXTENSIONS (4)
```
1. TitanAI-Voice
   └─ Works on: Chatbot (skills), AIAgent (actions), AIChatPro (connectors)
   ├─ Voice capture (skill)
   ├─ Call handling (action)
   ├─ Audio synthesis (connector)
   └─ Shared Twilio/Vonage integration

2. TitanAI-Channels
   └─ Works on: Chatbot (skills), AIAgent (actions), AIChatPro (connectors)
   ├─ Slack adapter
   ├─ WhatsApp adapter
   ├─ SMS adapter (future)
   └─ Telegram adapter (future)

3. TitanAI-Email
   └─ Works on: Chatbot (skills), AIAgent (actions), AIChatPro (connectors)
   ├─ Gmail read (skill)
   ├─ Send email (action)
   ├─ Email sync (connector)
   └─ Unified Gmail API client

4. TitanAI-Share
   └─ Works on: Chatbot (skills), AIAgent (actions), AIChatPro (feature)
   ├─ Share conversation (skill/action)
   ├─ Generate share link (action)
   └─ Collaboration features
```

### CHATBOT-SPECIFIC EXTENSIONS (3)
```
5. TitanAI-Commerce
   └─ Works on: Chatbot only
   ├─ Cart management
   ├─ Order processing
   ├─ Inventory checking
   ├─ Fulfillment tracking
   └─ Payment handling

6. TitanAI-Reservations
   └─ Works on: Chatbot only
   ├─ Appointment booking
   ├─ Calendar sync
   ├─ Availability checking
   └─ Confirmation handling

7. TitanAI-CRM
   └─ Works on: Chatbot only
   ├─ Customer segmentation
   ├─ Review collection
   ├─ Sentiment analysis
   ├─ Churn prediction
   └─ Customer profile unified API
```

### AIAGENT-SPECIFIC EXTENSIONS (1)
```
8. TitanAI-Agents
   └─ Works on: AIAgent only
   ├─ Chatbot tool
   ├─ Marketing tool
   ├─ Social media tool
   └─ Custom tool registry
```

### AICHATPRO-SPECIFIC EXTENSIONS (3)
```
9. TitanAI-Core
   └─ Works on: AIChatPro only
   ├─ Skill library
   ├─ Folder organization
   ├─ Global settings
   └─ Temporary conversations

10. TitanAI-Research
    └─ Works on: AIChatPro only
    ├─ Deep research capability
    ├─ File Q&A (RAG)
    └─ Smart image generation

11. TitanAI-UI
    └─ Works on: AIChatPro only
    ├─ Entity extraction & highlighting
    ├─ Context selection UI
    └─ Canvas/workspace
```

---

## Implementation Impact

### Changes to BaseExtensionServiceProvider

```php
// BEFORE (current)
abstract public function getExtensionType(): string;

// AFTER (supports both)
public function getExtensionType(): string {
    // Default: single type
    return array_values($this->getExtensionTypes())[0];
}

public function getExtensionTypes(): array {
    // Override to support multiple types
    return [$this->getExtensionType()];
}

public function boot(): void {
    foreach ($this->getExtensionTypes() as $type) {
        $this->registerComponentsToUnifiedRegistry($type);
        $this->subscribeToEvents($type);
    }
}
```

### Changes Required: ~50 lines in BaseExtensionServiceProvider

### Backwards Compatibility: ✅ 100%
- Existing single-type extensions work without changes
- Optional cross-type for new extensions
- No breaking changes

---

## Example: TitanAI-Voice Implementation

```php
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    
    public function getExtensionKey(): string {
        return 'titanai-voice';
    }
    
    // Works on all three systems
    public function getExtensionTypes(): array {
        return ['chatbot', 'aiagent', 'aichatpro'];
    }
    
    // Skills for Chatbot system
    protected function getSkills(): array {
        return [
            'voice-call' => new VoiceCallSkill(),
            'voice-message' => new VoiceMessageSkill(),
        ];
    }
    
    // Actions for AIAgent system
    protected function getActions(): array {
        return [
            'initiate-call' => new InitiateCallAction(),
            'transfer-call' => new TransferCallAction(),
            'end-call' => new EndCallAction(),
        ];
    }
    
    // Connectors for AIChatPro system
    protected function getConnectors(): array {
        return [
            'twilio' => new TwilioConnector(),
            'vonage' => new VonageConnector(),
        ];
    }
    
    protected function registerRoutes(): static {
        // Routes available to all three systems
        // Config routing intelligently based on caller
        return $this;
    }
}
```

---

## Configuration

### Single Type (Current - for type-specific extensions)
```php
config('titanai.extensions.chatbot.auto_register_to_unified_registry')
```

### Multiple Types (New - for cross-type extensions)
```php
// If extension works on multiple types, config checked for each:
config('titanai.extensions.chatbot.auto_register_to_unified_registry')
config('titanai.extensions.aiagent.auto_register_to_unified_registry')
config('titanai.extensions.aichatpro.auto_register_to_unified_registry')
```

---

## Benefits of Unified TitanAI Naming + Cross-Type

### Naming
- ✓ All extensions have TitanAI prefix (unified brand)
- ✓ Clear purpose (TitanAI-Voice not ChatbotVoice)
- ✓ Not confused with parent system

### Architecture
- ✓ Voice/Channels/Email work everywhere they're useful
- ✓ Domain features stay with their systems
- ✓ Eliminates duplicate implementations
- ✓ Shared infrastructure for integrations

### Developer Experience
- ✓ Clear which extensions work where
- ✓ Less confusion about relationships
- ✓ Obvious where to add new features

### Maintenance
- ✓ Voice integration logic in one place (not ChatbotVoice + separate implementation for AIAgent)
- ✓ Channel adapters in one place
- ✓ Email logic unified

---

## Recommendation

✅ **YES, use TitanAI prefix for all extensions**

✅ **YES, support cross-type for infrastructure extensions (4 of them)**
- TitanAI-Voice
- TitanAI-Channels
- TitanAI-Email
- TitanAI-Share

✅ **Keep type-specific for domain extensions (7 of them)**
- TitanAI-Commerce (Chatbot)
- TitanAI-Reservations (Chatbot)
- TitanAI-CRM (Chatbot)
- TitanAI-Agents (AIAgent)
- TitanAI-Core (AIChatPro)
- TitanAI-Research (AIChatPro)
- TitanAI-UI (AIChatPro)

### Effort to Implement
- Blueprint modification: ~2 hours
- Update naming: ~4 hours
- Test cross-type: ~8 hours
- Total: ~14 hours

### Timeline
- Update BaseExtensionServiceProvider (Phase 0 of existing blueprint)
- Deploy with all 4 cross-type extensions enabled
- Existing type-specific extensions work as-is

### Risk: LOW
- Backwards compatible
- No breaking changes
- Phased rollout possible

