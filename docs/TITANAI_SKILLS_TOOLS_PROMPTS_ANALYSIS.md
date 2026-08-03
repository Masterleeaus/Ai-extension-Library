# TitanAI Skills, Tools & Prompts Analysis

## Context: Three Different Component Types

Your TitanAI ecosystem has three main AI extensions, each with different component vocabularies:

```
CHATBOT (Chatbot Core)
├─ Skills: Discrete capabilities (e.g., "handle-voice-call", "read-email")
├─ Actions: User-facing operations (e.g., "book appointment")
└─ Connectors: External integrations (e.g., Twilio, Gmail API)

AIAGENT (AIAgent Core)
├─ Actions: What agents can do (e.g., "initiate-call", "post-to-slack")
├─ Tools: Callable functions for agents (e.g., calculator, web search)
└─ Connectors: External integrations (e.g., Slack API, Gmail API)

AICHATPRO (AIChatPro Core)
├─ Skills: Chat enhancements (e.g., "deep-research", "file-qa")
├─ Tools: Chat tools (e.g., calculator, code execution)
├─ Prompts: System/assistant prompts (e.g., "research-expert", "code-reviewer")
└─ Connectors: External integrations (e.g., OpenAI, Anthropic)
```

**Problem:** Naming inconsistency across systems

---

## Current State: Confusing Terminology

### Chatbot System
```
"Skill" = Discrete capability
  Example: VoiceCallSkill
  Purpose: Handle specific business logic
  Registration: UnifiedRegistry.registerSkill()
```

### AIAgent System
```
"Action" = What agents can execute
  Example: InitiateCallAction
  Purpose: Agent-callable operation
  Registration: UnifiedRegistry.registerAction()

"Tool" = Callable function with params/schema
  Example: class GoogleSearchTool implements ToolInterface
  Purpose: Agent can invoke via function calling
  Registration: UnifiedRegistry.registerTool()
```

### AIChatPro System
```
"Skill" = Chat enhancement
  Example: DeepResearchSkill
  Purpose: Augment chat capabilities
  Registration: UnifiedRegistry.registerSkill()

"Tool" = Chat tool (different from AIAgent tools)
  Example: CodeExecutorTool, CalculatorTool
  Purpose: Available in chat context
  Registration: UnifiedRegistry.registerTool()

"Prompt" = System/assistant prompt template
  Example: "research_expert_prompt.md"
  Purpose: Guide LLM behavior
  Registration: PromptRegistry.register()
```

**Issue:** "Skill" and "Tool" mean different things in different contexts

---

## Proposed Unified Naming

### Option A: System-Agnostic (Cleanest)

```
SKILLS (Universal)
├─ Discrete capabilities (all systems can register)
├─ Examples:
│  ├─ TitanAI-Voice/VoiceCallSkill
│  ├─ TitanAI-Channels/ReadSlackSkill
│  ├─ TitanAI-Email/ParseEmailSkill
│  └─ TitanAI-Research/DeepResearchSkill
└─ Registration: UnifiedRegistry.registerSkill('key', skill)

ACTIONS (Universal)
├─ Operations that can be executed
├─ Examples:
│  ├─ TitanAI-Voice/InitiateCallAction
│  ├─ TitanAI-Channels/PostToSlackAction
│  ├─ TitanAI-Commerce/AddToCartAction
│  └─ TitanAI-Email/SendEmailAction
└─ Registration: UnifiedRegistry.registerAction('key', action)

TOOLS (Universal)
├─ Callable functions with schemas (for function calling/tool use)
├─ Examples:
│  ├─ TitanAI-Voice/CallTransferTool
│  ├─ TitanAI-Research/WebSearchTool
│  ├─ TitanAI-Research/CodeExecutorTool
│  └─ TitanAI-Channels/SendMessageTool
└─ Registration: UnifiedRegistry.registerTool('key', tool)

PROMPTS (Universal)
├─ LLM system/assistant prompts
├─ Examples:
│  ├─ TitanAI-Research/research_expert_prompt
│  ├─ TitanAI-Research/code_reviewer_prompt
│  ├─ TitanAI-CRM/customer_service_prompt
│  └─ TitanAI-Voice/call_handler_prompt
└─ Registration: PromptRegistry.register('key', prompt)

CONNECTORS (Universal)
├─ External service integrations
├─ Examples:
│  ├─ TitanAI-Voice/TwilioConnector
│  ├─ TitanAI-Email/GmailConnector
│  ├─ TitanAI-Channels/SlackConnector
│  └─ TitanAI-Research/OpenAIConnector
└─ Registration: UnifiedRegistry.registerConnector('key', connector)
```

**Benefit:**
- ✓ Consistent naming across all systems
- ✓ Clear what each component is
- ✓ Can share components across systems
- ✓ No "Skill" confusion between systems

---

## Detailed Component Breakdown

### SKILLS (Discrete Capabilities)

**Definition:** Self-contained, reusable capability

**Use Cases:**
- Chatbot: "voice-call" skill (handle incoming voice)
- AIAgent: "research" skill (research capability for agents)
- AIChatPro: "deep-research" skill (chat enhancement)

**Structure:**
```php
interface SkillInterface {
    public function getName(): string;        // "voice-call"
    public function getDescription(): string; // What it does
    public function execute(array $params);   // Do the work
}

class VoiceCallSkill implements SkillInterface {
    public function getName(): string {
        return 'titanai-voice.call';
    }
    
    public function getDescription(): string {
        return 'Handle incoming voice calls';
    }
    
    public function execute(array $params) {
        // $params: ['number', 'extension', ...]
        // Register phone call, transcribe, route
    }
}
```

**Registration:**
```php
$registry->registerSkill('titanai-voice.call', new VoiceCallSkill());
// System can find via: $registry->getSkill('titanai-voice.call')
```

**Naming Convention:**
```
{extension-name}.{capability-name}
Example: titanai-voice.call
         titanai-email.parse
         titanai-channels.read-slack
         titanai-research.deep-search
```

---

### ACTIONS (Operations to Execute)

**Definition:** Something that can be invoked/executed by the system

**Use Cases:**
- Chatbot: "book appointment" action (user conversation action)
- AIAgent: "send email" action (agent can invoke)
- AIChatPro: "generate share link" action (chat feature)

**Structure:**
```php
interface ActionInterface {
    public function getName(): string;
    public function getDescription(): string;
    public function getParameters(): array;  // Input schema
    public function execute(array $params);  // Do the work
}

class InitiateCallAction implements ActionInterface {
    public function getName(): string {
        return 'titanai-voice.initiate-call';
    }
    
    public function getParameters(): array {
        return [
            'recipient' => ['type' => 'string', 'required' => true],
            'caller_id' => ['type' => 'string', 'required' => false],
        ];
    }
    
    public function execute(array $params) {
        // $params: ['recipient' => '+1234567890', ...]
        // Make the call via Twilio
    }
}
```

**Registration:**
```php
$registry->registerAction('titanai-voice.initiate', new InitiateCallAction());
```

**Naming Convention:**
```
{extension-name}.{action-name}
Example: titanai-voice.initiate-call
         titanai-email.send
         titanai-channels.post-message
         titanai-commerce.add-to-cart
```

---

### TOOLS (Callable Functions with Schemas)

**Definition:** Function-callable interfaces for LLM tool use / function calling

**Use Cases:**
- AIAgent: "search web" tool (agent can call via function calling)
- AIChatPro: "code executor" tool (chat can invoke)
- Chatbot: "calculator" tool (conversation can use)

**Structure:**
```php
interface ToolInterface {
    public function getName(): string;
    public function getDescription(): string;
    public function getInputSchema(): array;  // OpenAI/Claude function schema
    public function execute(array $params);
}

class WebSearchTool implements ToolInterface {
    public function getName(): string {
        return 'web_search';
    }
    
    public function getDescription(): string {
        return 'Search the web for information';
    }
    
    public function getInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Search query'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max results',
                    'default' => 10
                ]
            ],
            'required' => ['query']
        ];
    }
    
    public function execute(array $params) {
        // $params: ['query' => '...', 'limit' => 10]
        // Call search API, return results
    }
}
```

**Registration:**
```php
// For LLM function calling
$toolRegistry->registerTool('titanai-research.web-search', new WebSearchTool());

// LLM receives schema:
{
    "type": "function",
    "function": {
        "name": "titanai_research_web_search",
        "description": "Search the web for information",
        "parameters": { ... }
    }
}
```

**Naming Convention:**
```
{extension-name}.{tool-name}
Example: titanai-research.web-search
         titanai-research.code-executor
         titanai-voice.transcriber
         titanai-email.parser
```

---

### PROMPTS (LLM Instructions)

**Definition:** System/assistant prompts that guide LLM behavior

**Use Cases:**
- AIChatPro: "research expert" prompt (make chat behave as researcher)
- AIChatPro: "code reviewer" prompt (make chat review code)
- Chatbot: "customer service" prompt (guide voice handling)
- AIAgent: "email responder" prompt (guide agent email behavior)

**Structure:**
```php
interface PromptInterface {
    public function getName(): string;
    public function getDescription(): string;
    public function getSystemPrompt(array $context): string;  // The actual prompt
    public function getExamples(): array;  // Few-shot examples
}

class ResearchExpertPrompt implements PromptInterface {
    public function getName(): string {
        return 'research-expert';
    }
    
    public function getSystemPrompt(array $context): string {
        return <<<PROMPT
You are a research expert. Your role is to:
1. Find authoritative sources
2. Synthesize information
3. Provide citations
4. Highlight limitations

Current context:
- Topic: {$context['topic']}
- Depth: {$context['depth']}

Requirements:
- Be thorough and accurate
- Always cite sources
- Acknowledge uncertainty
PROMPT;
    }
    
    public function getExamples(): array {
        return [
            [
                'input' => 'What is quantum computing?',
                'output' => 'Quantum computing leverages quantum mechanics... [sources]'
            ]
        ];
    }
}
```

**Registration:**
```php
$promptRegistry->register('titanai-research.expert', new ResearchExpertPrompt());

// Use in chat:
$prompt = $promptRegistry->get('titanai-research.expert');
$systemPrompt = $prompt->getSystemPrompt(['topic' => 'AI', 'depth' => 'advanced']);
// Send to LLM as system message
```

**Naming Convention:**
```
{extension-name}.{prompt-name}
Example: titanai-research.expert
         titanai-research.code-reviewer
         titanai-crm.customer-service
         titanai-voice.call-handler
```

---

## Component Matrix: Which Systems Use What?

```
Component    | Chatbot | AIAgent | AIChatPro
─────────────┼─────────┼─────────┼──────────
Skills       | ✓ Yes   | ✓ Yes   | ✓ Yes
Actions      | ✓ Yes   | ✓ Yes   | ✓ Yes
Tools        | ✓ Yes   | ✓ Yes   | ✓ Yes
Prompts      | ✓ Yes   | ✓ Yes   | ✓ Yes
Connectors   | ✓ Yes   | ✓ Yes   | ✓ Yes
```

**All systems use all component types!**

This means:
- ✓ Naming should be unified across all systems
- ✓ Registry should be shared (UnifiedRegistry)
- ✓ Components can be cross-type compatible

---

## Unified Registry Architecture

### Single Registry for Everything

```php
class UnifiedRegistry {
    // Skills
    public function registerSkill(string $key, SkillInterface $skill): void
    public function getSkill(string $key): ?SkillInterface
    public function getSkills(string $filter = null): array
    
    // Actions
    public function registerAction(string $key, ActionInterface $action): void
    public function getAction(string $key): ?ActionInterface
    public function getActions(string $filter = null): array
    
    // Tools
    public function registerTool(string $key, ToolInterface $tool): void
    public function getTool(string $key): ?ToolInterface
    public function getTools(string $filter = null): array
    public function getToolsForFunctionCalling(): array  // Formatted for LLM
    
    // Prompts
    public function registerPrompt(string $key, PromptInterface $prompt): void
    public function getPrompt(string $key): ?PromptInterface
    public function getPrompts(string $filter = null): array
    
    // Connectors
    public function registerConnector(string $key, ConnectorInterface $connector): void
    public function getConnector(string $key): ?ConnectorInterface
    public function getConnectors(string $filter = null): array
    
    // Query all
    public function getComponentsByExtension(string $extension): array
    public function getComponentsBySystem(string $system): array
    public function getSummary(): array
}
```

### Usage Example

```php
// Chatbot registers voice components
$registry->registerSkill('titanai-voice.call', new VoiceCallSkill());
$registry->registerAction('titanai-voice.answer', new AnswerCallAction());
$registry->registerTool('titanai-voice.transfer', new CallTransferTool());
$registry->registerPrompt('titanai-voice.handler', new CallHandlerPrompt());
$registry->registerConnector('titanai-voice.twilio', new TwilioConnector());

// AIAgent uses same components differently
$agent = new Agent();
$agent->setTools($registry->getTools('titanai-voice.*'));  // Get all voice tools
$agent->setSystemPrompt($registry->getPrompt('titanai-voice.handler'));

// AIChatPro also uses them
$chat = new ChatSession();
$chat->setAvailableTools($registry->getToolsForFunctionCalling('titanai-voice.*'));
$chat->setSystemPrompt($registry->getPrompt('titanai-voice.handler'));

// Query across all systems
$registry->getSummary();
// {
//   "skills": 27,
//   "actions": 15,
//   "tools": 42,
//   "prompts": 8,
//   "connectors": 12,
//   "total": 104,
//   "by_extension": { "titanai-voice": 12, ... },
//   "by_system": { "chatbot": 35, "aiagent": 40, "aichatpro": 29 }
// }
```

---

## Naming Convention Rules

### Extension Prefix
```
All components should be namespaced by extension:
{extension-name}.{component-name}

Examples:
  titanai-voice.call
  titanai-email.send
  titanai-channels.post-slack
  titanai-research.deep-search
```

### Naming Standards

**Skills:**
```
{extension}.{action-noun}
Examples:
  titanai-voice.call-handling
  titanai-email.parsing
  titanai-channels.slack-sync
  titanai-research.deep-search
```

**Actions:**
```
{extension}.{verb}-{object}
Examples:
  titanai-voice.initiate-call
  titanai-email.send-message
  titanai-channels.post-to-slack
  titanai-commerce.add-to-cart
```

**Tools:**
```
{extension}.{tool-name}
Examples:
  titanai-research.web-search
  titanai-research.code-executor
  titanai-voice.call-transcriber
  titanai-email.attachment-parser
```

**Prompts:**
```
{extension}.{role-or-purpose}
Examples:
  titanai-research.expert
  titanai-research.code-reviewer
  titanai-crm.customer-service
  titanai-voice.call-handler
```

**Connectors:**
```
{extension}.{provider-name}
Examples:
  titanai-voice.twilio
  titanai-voice.vonage
  titanai-email.gmail
  titanai-channels.slack
```

---

## Implementation in BaseExtensionServiceProvider

### Current Structure (from blueprint)

```php
abstract class BaseExtensionServiceProvider extends ServiceProvider {
    
    // Components to register
    protected function getSkills(): array {
        return [];
    }
    
    protected function getActions(): array {
        return [];
    }
    
    protected function getConnectors(): array {
        return [];
    }
    
    protected function getTools(): array {
        return [];
    }
    
    // Register all to unified registry
    protected function registerComponentsToUnifiedRegistry(string $type): void {
        $registry = app(UnifiedRegistry::class);
        
        foreach ($this->getSkills() as $key => $skill) {
            $registry->registerSkill($this->getExtensionKey() . '.' . $key, $skill);
        }
        
        foreach ($this->getActions() as $key => $action) {
            $registry->registerAction($this->getExtensionKey() . '.' . $key, $action);
        }
        
        foreach ($this->getTools() as $key => $tool) {
            $registry->registerTool($this->getExtensionKey() . '.' . $key, $tool);
        }
        
        foreach ($this->getConnectors() as $key => $connector) {
            $registry->registerConnector($this->getExtensionKey() . '.' . $key, $connector);
        }
    }
}
```

### Example: TitanAI-Voice Extension

```php
class TitanAIVoiceServiceProvider extends BaseExtensionServiceProvider {
    
    public function getExtensionKey(): string {
        return 'titanai-voice';
    }
    
    protected function getSkills(): array {
        return [
            'call' => new VoiceCallSkill(),
            'answer' => new AnswerCallSkill(),
            'transcribe' => new TranscribeSkill(),
        ];
    }
    
    protected function getActions(): array {
        return [
            'initiate-call' => new InitiateCallAction(),
            'transfer-call' => new TransferCallAction(),
            'end-call' => new EndCallAction(),
        ];
    }
    
    protected function getTools(): array {
        return [
            'transcriber' => new TranscriberTool(),
            'call-transfer' => new CallTransferTool(),
            'conference' => new ConferenceTool(),
        ];
    }
    
    protected function getConnectors(): array {
        return [
            'twilio' => new TwilioConnector(),
            'vonage' => new VonageConnector(),
        ];
    }
}

// Automatic naming:
// Skills:     titanai-voice.call, titanai-voice.answer, titanai-voice.transcribe
// Actions:    titanai-voice.initiate-call, titanai-voice.transfer-call, etc.
// Tools:      titanai-voice.transcriber, titanai-voice.call-transfer, etc.
// Connectors: titanai-voice.twilio, titanai-voice.vonage
```

---

## Adding Prompts Support

### Extend BaseExtensionServiceProvider

```php
abstract class BaseExtensionServiceProvider extends ServiceProvider {
    
    // New method for prompts
    protected function getPrompts(): array {
        return [];
    }
    
    // Register prompts to registry
    protected function registerComponentsToUnifiedRegistry(string $type): void {
        // ... existing code ...
        
        foreach ($this->getPrompts() as $key => $prompt) {
            $registry->registerPrompt($this->getExtensionKey() . '.' . $key, $prompt);
        }
    }
}
```

### Example: TitanAI-Research Extension

```php
class TitanAIResearchServiceProvider extends BaseExtensionServiceProvider {
    
    public function getExtensionKey(): string {
        return 'titanai-research';
    }
    
    protected function getPrompts(): array {
        return [
            'expert' => new ResearchExpertPrompt(),
            'code-reviewer' => new CodeReviewerPrompt(),
            'analyst' => new DataAnalystPrompt(),
        ];
    }
    
    protected function getTools(): array {
        return [
            'web-search' => new WebSearchTool(),
            'code-executor' => new CodeExecutorTool(),
            'document-parser' => new DocumentParserTool(),
        ];
    }
}

// Automatic naming:
// Prompts: titanai-research.expert, titanai-research.code-reviewer, etc.
// Tools:   titanai-research.web-search, titanai-research.code-executor, etc.
```

---

## Benefits of Unified Naming

### Clarity
```
Before (Confusing):
  Chatbot: "VoiceCallSkill"
  AIAgent: "InitiateCallAction"
  AIChatPro: Uses both as "tools"
  Result: Developers confused about relationships

After (Clear):
  All use: "titanai-voice.{component-type}"
  Result: Obvious it's the same capability across systems
```

### Discoverability
```
Get all voice components:
  $registry->getComponentsByExtension('titanai-voice')
  // Returns all skills, actions, tools, prompts, connectors

Get all tools for LLM:
  $registry->getToolsForFunctionCalling('titanai-research.*')
  // Returns formatted for Claude/GPT function calling

Get all prompts:
  $registry->getPrompts('titanai-.*')
  // All prompts across all extensions
```

### Reusability
```
TitanAI-Voice defines one Twilio connector.
All three systems (Chatbot, AIAgent, AIChatPro) use it.
Update once, all systems benefit.
```

### Consistency
```
Every component follows same pattern:
  {extension-name}.{component-type}.{component-name}
  
Makes it easy to:
  - Find components
  - Document components
  - Version components
  - Test components
```

---

## Summary: Skills, Tools & Prompts

| Component | Definition | Use Cases | Registry |
|-----------|-----------|-----------|----------|
| **Skill** | Discrete capability | Chatbot voice handling, AIAgent research, AIChatPro enhancement | UnifiedRegistry.registerSkill() |
| **Action** | Operation to execute | Book appointment, send email, post message | UnifiedRegistry.registerAction() |
| **Tool** | LLM-callable function | Web search, code execution, document parsing | UnifiedRegistry.registerTool() |
| **Prompt** | LLM system prompt | Research expert, code reviewer, customer service | UnifiedRegistry.registerPrompt() |
| **Connector** | External integration | Twilio, Gmail, Slack, OpenAI | UnifiedRegistry.registerConnector() |

**All use unified naming:** `{extension-name}.{component-name}`

**All registered to:** UnifiedRegistry

**All available to:** Chatbot, AIAgent, AIChatPro (cross-type compatible)

