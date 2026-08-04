# Issue #20: Harden PhoneCallAgent Webhooks and ElevenLabs Booking-Tool Callbacks

**Status:** CRITICAL | Phase 1  
**Priority:** Critical - Security  
**Effort:** 1-2 weeks  
**Depends on:** #143, #145, #146  
**Blocks:** #21, #60, #67  

## Problem Statement

**Confirmed defects:**
- PhoneCallAgent signature checks fail open when Twilio token or ElevenLabs webhook secret is missing
- ElevenLabs timestamp freshness not enforced
- Public ElevenLabs tool endpoint does not invoke webhook verifier
- URL agent UUID used to perform booking actions (authorization bypass)
- Raw body logged in responses (credential exposure)
- Internal exception messages returned to client

## Root Causes

1. **Missing Credential Validation:** Webhook processing continues without required vault-backed credentials
2. **Incomplete Verification:** Signature verification optional or incomplete
3. **No Replay Prevention:** Same event can execute multiple times
4. **Missing Authorization:** Tool callbacks bypass agent/action authorization checks
5. **Insecure Logging:** Raw bodies and exception details logged and exposed

## Solution Requirements

Immediate containment and full hardening of PhoneCallAgent webhook and booking tool security.

## Immediate Containment (NO DEPENDENCIES)

### 1. Disable Webhooks Without Credentials
```php
// extensions/PhoneCallAgent/Http/Controllers/Webhook/TwilioWebhookController.php

public function handleInbound(Request $request): Response {
    // Require Twilio token from vault FIRST
    $authToken = $this->vault->retrieve(
        $this->tenantContext->getTenantId(),
        'twilio-auth-token'
    );
    
    // ❌ WRONG: if (!$authToken) { log('missing'); process anyway; }
    // ✅ CORRECT:
    if (!$authToken) {
        Log::warning("Webhook received but Twilio credentials not configured");
        return response('Unauthorized - credentials not configured', 401);
    }
    
    // Only process if credentials exist
    return $this->processWebhook($request, $authToken);
}
```

### 2. Stop Failing Open on Invalid Signatures
```php
// Verify signature is VALID, not just PRESENT

public function verifyTwilioSignature(Request $request, string $authToken): bool {
    $signature = $request->header('X-Twilio-Signature');
    
    // ❌ WRONG: if (!$signature) return true; (fail open)
    // ✅ CORRECT:
    if (!$signature) {
        return false;  // Fail closed
    }
    
    $expectedSignature = hash_hmac(
        'sha1',
        $request->url() . http_build_query($request->all()),
        $authToken
    );
    
    return hash_equals($expectedSignature, $signature);
}
```

### 3. Disable ElevenLabs Tool Callbacks Without Verification
```php
// extensions/PhoneCallAgent/Http/Controllers/Webhook/ElevenLabsToolController.php

public function handleToolCallback(Request $request): Response {
    // Require ElevenLabs secret from vault
    $secret = $this->vault->retrieve(
        $this->tenantContext->getTenantId(),
        'elevenlabs-api-key'
    );
    
    // ❌ WRONG: Process without verification
    // ✅ CORRECT:
    if (!$secret) {
        Log::warning("Tool callback received but ElevenLabs credentials not configured");
        return response('Unauthorized', 401);
    }
    
    // Verify signature
    if (!$this->verifyElevenLabsSignature($request, $secret)) {
        Log::warning("Invalid ElevenLabs tool callback signature");
        return response('Invalid signature', 401);
    }
    
    return $this->processToolCallback($request);
}
```

### 4. Enforce Timestamp Freshness
```php
public function verifyElevenLabsSignature(Request $request, string $secret): bool {
    $payload = $request->getContent();
    $signature = $request->header('X-ElevenLabs-Signature');
    
    if (!$signature) {
        return false;
    }
    
    // Parse payload to get timestamp
    $data = json_decode($payload, true);
    $timestamp = $data['timestamp'] ?? null;
    
    if (!$timestamp) {
        return false;
    }
    
    // ❌ WRONG: Don't check timestamp freshness
    // ✅ CORRECT: Enforce 5-minute window
    $requestTime = strtotime($timestamp);
    $currentTime = time();
    $age = abs($currentTime - $requestTime);
    
    if ($age > 300) {  // 5 minutes
        Log::warning("ElevenLabs webhook timestamp too old", ['age_seconds' => $age]);
        return false;
    }
    
    // Verify signature
    $expectedSignature = hash_hmac(
        'sha256',
        $payload,
        $secret,
        true  // binary
    );
    $expectedSignatureB64 = base64_encode($expectedSignature);
    
    return hash_equals($expectedSignatureB64, $signature);
}
```

### 5. Disable Unsigned Tool Callbacks
```php
// ❌ WRONG: Accept unsigned callbacks with URL-based agent ID
// app/Http/Controllers/Api/ElevenLabsPublicToolController.php (VULNERABLE)
//
// public function executeToolFromUrl(Request $request, string $agentUuid) {
//     $agent = Agent::where('uuid', $agentUuid)->firstOrFail();  // ❌ Bypass!
//     $result = $agent->executeTool($request->all());            // ❌ No verification!
//     return $result;
// }

// ✅ CORRECT: Only signed webhook requests allowed
class ElevenLabsToolController extends WebhookHandler {
    protected function getProvider(): string {
        return 'elevenlabs';
    }
    
    protected function processWebhook(WebhookRequest $request): mixed {
        // 1. Signature already verified by base class
        
        // 2. Resolve agent and user from request context
        $agentId = $request->payload['agent_id'] ?? null;
        $userId = $request->payload['user_id'] ?? null;
        
        if (!$agentId || !$userId) {
            throw new \InvalidArgumentException("Missing agent or user ID");
        }
        
        // 3. Verify agent belongs to tenant
        $agent = Agent::where('id', $agentId)
            ->where('tenant_id', $request->tenantId)
            ->firstOrFail();
        
        // 4. Authorize tool execution
        if (!$this->authorizationPolicy->canExecuteTool(
            $this->tenantContext,
            $agent,
            $request->payload['tool_name'] ?? null
        )) {
            throw new \UnauthorizedException("Not authorized to execute tool");
        }
        
        // 5. Execute tool through governance
        return $this->toolGateway->executeTool(
            $agent,
            $request->payload['tool_name'],
            $request->payload['parameters'] ?? []
        );
    }
}
```

### 6. Stop Logging Raw Bodies and Exceptions
```php
// ❌ WRONG:
Log::info("Webhook received", ['body' => $request->getContent()]);
// Returns internal exception message:
return response(['error' => $e->getMessage()], 500);

// ✅ CORRECT:
Log::info("Webhook received", [
    'provider' => 'twilio',
    'event_type' => $request->input('MessageStatus'),
    'timestamp' => $request->input('Timestamp')
    // NO raw body, NO credentials, NO exception text
]);

// Return stable error codes
return response(['error' => 'PROCESSING_ERROR'], 500);
```

## Full Implementation Requirements

### Phase 1: Dependencies (Complete First)
- [ ] #143: TenantContext & Authorization Policies
- [ ] #145: Credential Vault References
- [ ] #146: Webhook Verification & Replay Prevention

### Phase 2: Webhook Hardening

**Twilio Webhook Security:**
```php
// 1. Require auth token before processing
// 2. Validate signature against raw URL and body
// 3. Enforce timestamp freshness (5 min)
// 4. Use idempotency keys to prevent duplicates
// 5. Resolve tenant from webhook context
// 6. Don't expose exception details
```

**ElevenLabs Webhook Security:**
```php
// 1. Require API key before processing
// 2. Validate HMAC-SHA256 signature
// 3. Enforce timestamp freshness
// 4. Block public tool endpoint (use webhook only)
// 5. Resolve agent and user from payload
// 6. Authorize each tool execution
// 7. Route through governed execution
```

**OpenAI Realtime Security (if used):**
```php
// 1. Validate OAuth token
// 2. Implement session-based security
// 3. Enforce rate limiting
// 4. Monitor for abuse
```

### Phase 3: Authorization & Governance

**Tool Execution Authorization:**
```php
// Every tool call must:
// 1. Have an authenticated user context
// 2. Have an authorized agent
// 3. Check tool-specific permissions
// 4. Get approval if needed (from #63)
// 5. Route through governance layer
```

**Booking Operations:**
```php
// Booking actions must:
// 1. Be authorized through governance
// 2. Include receipt for audit trail
// 3. Be reversible (rollback capability)
// 4. Include user acknowledgment
// 5. Not bypass WorkCore (from #49)
```

### Phase 4: Idempotency

**Prevent Duplicate Mutations:**
```php
// For each webhook event:
// 1. Generate idempotency key (provider event ID + type)
// 2. Check if already processed
// 3. If yes, return cached result
// 4. If no, process and cache result
// 5. Expiration: 24 hours
```

### Phase 5: Redaction & Logging

**Security Audit Trail:**
```php
// Log:
// ✅ Event received (timestamp, type, provider)
// ✅ Verification result (passed/failed, reason)
// ✅ Authorization result (approved/denied)
// ✅ Tool execution (tool name, parameters outline, result outline)
// ✅ Any security violations (rejections)

// Never log:
// ❌ Raw webhook body
// ❌ Credentials or tokens
// ❌ Exception stack traces
// ❌ Full user/agent data
// ❌ Sensitive payload fields
```

## Exit Criteria (All must pass)

- ✅ Missing credentials disables webhook processing
- ✅ Invalid signatures rejected
- ✅ ElevenLabs timestamp freshness enforced
- ✅ Booking actions require authorization
- ✅ Tool callbacks require valid webhook signature
- ✅ Public tool endpoint removed or secured
- ✅ Duplicate webhook events handled idempotently
- ✅ Cross-tenant tool access prevented
- ✅ No sensitive data in logs
- ✅ Error codes stable (no exception text)
- ✅ All tests pass (including replay scenarios)

## Testing Requirements

1. **Webhook Verification Tests:**
   - Missing credentials → rejected
   - Invalid signature → rejected
   - Valid signature → accepted
   - Expired timestamp → rejected
   - Duplicate event → idempotent result

2. **Tool Authorization Tests:**
   - Unsigned tool callback → rejected
   - Tool call without authorization → rejected
   - Tool call with authorization → executed
   - Cross-tenant tool access → rejected

3. **Booking Tests:**
   - Booking through unsigned callback → rejected
   - Booking through verified callback → executed through governance
   - Booking result includes receipt
   - Booking rollback works

4. **Security Tests:**
   - No credentials in logs
   - No exception text in responses
   - Replayed webhooks return same result
   - Rate limiting active

## Files to Create/Modify

```
extensions/PhoneCallAgent/Http/Controllers/
  └── Webhook/TwilioWebhookController.php (MODIFY)
  └── Webhook/ElevenLabsWebhookController.php (MODIFY)
  └── Webhook/ElevenLabsToolController.php (MODIFY - SECURE)

extensions/PhoneCallAgent/System/Services/
  └── PhoneCallAgentService.php (MODIFY - verification)

extensions/PhoneCallAgent/Tests/
  └── WebhookSecurityTest.php (NEW)
  └── BookingAuthorizationTest.php (NEW)
  └── ToolCallbackTest.php (NEW)

app/Http/Controllers/Api/
  └── ElevenLabsPublicToolController.php (DELETE - VULNERABLE!)

config/phonecallagent.php
  └── (MODIFY - remove plaintext secrets)
```

## Acceptance Criteria Checklist

- [ ] Webhooks require valid credentials
- [ ] All signatures verified
- [ ] Timestamp freshness enforced
- [ ] Public tool endpoint secured/removed
- [ ] Tool callbacks verified
- [ ] Authorization enforced
- [ ] Idempotency working
- [ ] No sensitive data logged
- [ ] All security tests passing
- [ ] No regression in existing functionality
- [ ] Documentation updated

## Related Issues

- #143: TenantContext & Authorization Policies
- #145: Credential Vault References
- #146: Webhook Verification & Replay Prevention
- #60: Build Voice Engine
- #21: Migrate ChatbotVoice
- #63: Governed Booking Operations (if exists)
- #49: Governed Tools/Actions (if exists)

## Next Steps

1. Implement immediate containment
2. Migrate to WebhookHandler base class
3. Implement authorization checks
4. Add idempotency handling
5. Write comprehensive security tests
6. Merge to main
