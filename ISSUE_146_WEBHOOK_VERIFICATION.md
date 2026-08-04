# Issue #146: Webhook Verification & Replay Prevention Tests

**Status:** URGENT | Phase 0 | Foundation  
**Priority:** Critical  
**Effort:** 1 week  
**Depends on:** #143, #145  
**Blocks:** #20, #61  

## Problem Statement

Webhook handlers across multiple extensions lack proper verification and replay prevention:
- Twilio webhook signatures not validated
- ElevenLabs webhook secrets not verified
- Meta (WhatsApp) signature verification incomplete
- No replay attack prevention
- No timestamp freshness enforcement

## Solution Requirements

Implement comprehensive webhook verification and replay prevention with proper signature validation, timestamp enforcement, and complete test coverage.

## Deliverables

### 1. Define Webhook Verification Contract

```php
// app/Domains/Shared/Webhooks/WebhookVerification.php

interface WebhookVerification {
    /**
     * Verify webhook signature and timestamp
     */
    public function verify(WebhookRequest $request): bool;
    
    /**
     * Get verification details for logging/audit
     */
    public function getVerificationDetails(WebhookRequest $request): VerificationDetails;
}

class WebhookRequest {
    public string $provider;           // "twilio", "elevenlabs", "meta", "slack", "gmail"
    public string $tenantId;
    public string $signature;          // Signature from headers
    public string $rawBody;            // Raw request body
    public string $timestamp;          // Webhook timestamp
    public string $url;                // Full webhook URL
    public array $headers;             // All headers
    public array $payload;             // Parsed body
}

class VerificationDetails {
    public bool $isValid;
    public string $reason;
    public string $provider;
    public string $signatureMethod;    // "hmac-sha256", "rsa", etc.
    public bool $timestampValid;
    public int $secondsOld;
}
```

### 2. Provider-Specific Verifiers

```php
// app/Domains/Shared/Webhooks/Verifiers/TwilioVerifier.php

class TwilioVerifier implements WebhookVerification {
    private const MAX_AGE_SECONDS = 300;  // 5 minutes
    
    public function verify(WebhookRequest $request): bool {
        // 1. Get auth token from vault
        $authToken = $this->vault->retrieve($request->tenantId, 'twilio-auth-token');
        if (!$authToken) {
            return false;  // No credentials = deny
        }
        
        // 2. Verify signature using Twilio's algorithm
        $expectedSignature = hash_hmac(
            'sha1',
            $request->url . http_build_query($request->payload),
            $authToken
        );
        
        if (!hash_equals($expectedSignature, $request->signature)) {
            return false;
        }
        
        // 3. Verify timestamp freshness
        $requestTime = (int) $request->timestamp;
        $currentTime = time();
        $age = abs($currentTime - $requestTime);
        
        if ($age > self::MAX_AGE_SECONDS) {
            return false;  // Too old - replay attack
        }
        
        return true;
    }
}

// app/Domains/Shared/Webhooks/Verifiers/ElevenLabsVerifier.php

class ElevenLabsVerifier implements WebhookVerification {
    private const MAX_AGE_SECONDS = 300;
    
    public function verify(WebhookRequest $request): bool {
        // 1. Get API key from vault
        $apiKey = $this->vault->retrieve($request->tenantId, 'elevenlabs-api-key');
        if (!$apiKey) {
            return false;
        }
        
        // 2. Verify signature: HMAC-SHA256
        $expectedSignature = hash_hmac(
            'sha256',
            $request->rawBody,
            $apiKey,
            true  // binary
        );
        $expectedSignatureB64 = base64_encode($expectedSignature);
        
        if (!hash_equals($expectedSignatureB64, $request->signature)) {
            return false;
        }
        
        // 3. Verify timestamp freshness
        $payload = json_decode($request->rawBody, true);
        $requestTime = $payload['timestamp'] ?? null;
        if (!$requestTime) {
            return false;
        }
        
        $currentTime = time();
        $age = abs($currentTime - $requestTime);
        
        if ($age > self::MAX_AGE_SECONDS) {
            return false;
        }
        
        return true;
    }
}

// app/Domains/Shared/Webhooks/Verifiers/MetaVerifier.php (WhatsApp)

class MetaVerifier implements WebhookVerification {
    public function verify(WebhookRequest $request): bool {
        // Get app secret from vault
        $appSecret = $this->vault->retrieve($request->tenantId, 'meta-app-secret');
        if (!$appSecret) {
            return false;
        }
        
        // Meta: X-Hub-Signature-256 = sha256=hash
        $xHubSignature = $request->signature;  // "sha256=..."
        
        if (!str_starts_with($xHubSignature, 'sha256=')) {
            return false;
        }
        
        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->rawBody,
            $appSecret
        );
        
        return hash_equals($xHubSignature, $expectedSignature);
    }
}

// app/Domains/Shared/Webhooks/Verifiers/SlackVerifier.php

class SlackVerifier implements WebhookVerification {
    private const MAX_AGE_SECONDS = 300;
    
    public function verify(WebhookRequest $request): bool {
        // Get signing secret from vault
        $signingSecret = $this->vault->retrieve($request->tenantId, 'slack-signing-secret');
        if (!$signingSecret) {
            return false;
        }
        
        // Slack: X-Slack-Request-Timestamp and X-Slack-Signature
        $timestamp = $request->headers['X-Slack-Request-Timestamp'] ?? null;
        $signature = $request->headers['X-Slack-Signature'] ?? null;
        
        if (!$timestamp || !$signature) {
            return false;
        }
        
        // 1. Check timestamp freshness
        $currentTime = time();
        $age = abs($currentTime - (int) $timestamp);
        if ($age > self::MAX_AGE_SECONDS) {
            return false;  // Replay attack
        }
        
        // 2. Verify signature
        $baseString = "v0:$timestamp:" . $request->rawBody;
        $expectedSignature = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}

// app/Domains/Shared/Webhooks/Verifiers/GmailVerifier.php

class GmailVerifier implements WebhookVerification {
    public function verify(WebhookRequest $request): bool {
        // Gmail uses OAuth, so token validation is different
        // Verify the request came from Google's IP ranges
        
        $senderIp = $request->headers['X-Forwarded-For'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if (!$this->isGoogleIp($senderIp)) {
            return false;
        }
        
        // Verify JWT signature in Authorization header
        $authHeader = $request->headers['Authorization'] ?? null;
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }
        
        $token = substr($authHeader, 7);
        // Verify JWT is valid (using Google's public keys)
        return $this->verifyJwt($token);
    }
    
    private function isGoogleIp(string $ip): bool {
        // Check against Google's published IP ranges
        $googleRanges = $this->getGoogleIpRanges();
        return $this->ipInRange($ip, $googleRanges);
    }
}
```

### 3. Replay Attack Prevention

```php
// app/Domains/Shared/Webhooks/ReplayPrevention.php

class ReplayPrevention {
    /**
     * Track webhook IDs and timestamps to prevent replays
     */
    public function registerWebhookEvent(
        string $tenantId,
        string $provider,
        string $webhookId,
        string $timestamp
    ): void {
        // Store in cache/database with expiration
        $key = "$provider:$webhookId:$timestamp";
        
        // Check if already seen
        if (cache()->has($key)) {
            throw new DuplicateWebhookException("Webhook replay detected");
        }
        
        // Record this webhook
        cache()->put($key, true, 3600);  // Keep for 1 hour
    }
    
    /**
     * Idempotency for webhook processing
     */
    public function recordWebhookProcessing(
        string $tenantId,
        string $webhookId,
        $result
    ): void {
        // Use idempotency ledger from #144
        $this->idempotencyLedger->recordIdempotentAction(
            $tenantId,
            "webhook:$webhookId",
            $result,
            now()->addHours(24)
        );
    }
}
```

### 4. Webhook Handler Base Class

```php
// app/Domains/Shared/Webhooks/WebhookHandler.php

abstract class WebhookHandler {
    protected WebhookVerification $verifier;
    protected ReplayPrevention $replayPrevention;
    protected IdempotencyLedger $idempotencyLedger;
    protected TenantContext $tenantContext;
    
    /**
     * Handle incoming webhook with full verification
     */
    public function handle(Request $request): Response {
        // 1. Extract tenant from URL or webhook header
        $tenantId = $this->resolveTenant($request);
        if (!$tenantId) {
            return response('Unauthorized', 401);
        }
        
        // 2. Create webhook request object
        $webhookRequest = new WebhookRequest(
            provider: $this->getProvider(),
            tenantId: $tenantId,
            signature: $request->header('X-Signature') ?? $request->header('X-Hub-Signature-256'),
            rawBody: $request->getContent(),
            timestamp: $request->header('X-Timestamp') ?? time(),
            url: $request->url(),
            headers: $request->headers->all(),
            payload: $request->json()->all() ?? []
        );
        
        // 3. Verify webhook signature
        if (!$this->verifier->verify($webhookRequest)) {
            $this->auditLog->warn("Invalid webhook signature", $webhookRequest);
            return response('Invalid signature', 401);
        }
        
        // 4. Check for replays
        try {
            $webhookId = $this->extractWebhookId($webhookRequest->payload);
            $this->replayPrevention->registerWebhookEvent(
                $tenantId,
                $this->getProvider(),
                $webhookId,
                $webhookRequest->timestamp
            );
        } catch (DuplicateWebhookException $e) {
            $this->auditLog->warn("Webhook replay detected", ['webhook_id' => $webhookId]);
            // Return success anyway (idempotent)
            return response('OK', 200);
        }
        
        // 5. Check idempotency
        $idempotencyKey = "webhook:$webhookId";
        if ($this->idempotencyLedger->hasBeenProcessed($tenantId, $idempotencyKey)) {
            return $this->idempotencyLedger->getResult($tenantId, $idempotencyKey);
        }
        
        // 6. Set tenant context
        $this->tenantContext->setCurrentTenant($tenantId);
        
        // 7. Process webhook
        try {
            $result = $this->processWebhook($webhookRequest);
            
            // 8. Record processing result for idempotency
            $this->idempotencyLedger->recordIdempotentAction(
                $tenantId,
                $idempotencyKey,
                $result,
                now()->addHours(24)
            );
            
            return response('OK', 200);
        } catch (\Exception $e) {
            $this->auditLog->error("Webhook processing failed", [
                'error' => $e->getMessage(),
                'webhook_id' => $webhookId
            ]);
            return response('Processing failed', 500);
        }
    }
    
    abstract protected function getProvider(): string;
    abstract protected function processWebhook(WebhookRequest $request): mixed;
    abstract protected function extractWebhookId(array $payload): string;
    abstract protected function resolveTenant(Request $request): ?string;
}
```

### 5. Update Existing Webhook Handlers

Update all webhook endpoints to extend WebhookHandler:

```php
// extensions/PhoneCallAgent/Http/Controllers/Webhook/TwilioWebhookController.php
class TwilioWebhookController extends WebhookHandler {
    protected function getProvider(): string {
        return 'twilio';
    }
    
    protected function processWebhook(WebhookRequest $request): mixed {
        // Process Twilio webhook
    }
}

// extensions/AIAgentGmail/Http/Controllers/GmailWebhookController.php
class GmailWebhookController extends WebhookHandler {
    protected function getProvider(): string {
        return 'gmail';
    }
    
    protected function processWebhook(WebhookRequest $request): mixed {
        // Process Gmail webhook
    }
}

// extensions/AIAgentSlackChannel/Http/Controllers/SlackWebhookController.php
class SlackWebhookController extends WebhookHandler {
    protected function getProvider(): string {
        return 'slack';
    }
    
    protected function processWebhook(WebhookRequest $request): mixed {
        // Process Slack webhook
    }
}

// extensions/AIAgentWhatsappChannel/Http/Controllers/WhatsappWebhookController.php
class WhatsappWebhookController extends WebhookHandler {
    protected function getProvider(): string {
        return 'meta';
    }
    
    protected function processWebhook(WebhookRequest $request): mixed {
        // Process WhatsApp webhook
    }
}
```

## Exit Criteria (All must pass)

- ✅ WebhookVerification interface implemented
- ✅ Provider-specific verifiers for all webhooks
- ✅ Replay attack prevention working
- ✅ Timestamp freshness enforced
- ✅ Credentials required before webhook processing
- ✅ All webhook handlers use base class
- ✅ Comprehensive webhook tests passing
- ✅ No unsigned webhooks processed

## Testing Requirements

1. **Verification Tests:**
   - Valid signature passes
   - Invalid signature rejected
   - Missing credentials deny webhook
   - Expired timestamp rejected

2. **Replay Tests:**
   - Duplicate webhook ID rejected
   - Same event twice returns idempotent result
   - Replays don't duplicate mutations

3. **Provider Tests:**
   - Twilio signature validation
   - ElevenLabs signature validation
   - Meta (WhatsApp) signature validation
   - Slack signature validation
   - Gmail token validation

4. **Integration Tests:**
   - End-to-end webhook flow works
   - Failed verification logged properly
   - Replay detected and logged
   - Webhook results idempotent

## Implementation Phases

### Phase 1: Interfaces (Day 1)
- Create WebhookVerification interface
- Create WebhookRequest class
- Create ReplayPrevention class

### Phase 2: Verifiers (Day 2-3)
- Implement TwilioVerifier
- Implement ElevenLabsVerifier
- Implement MetaVerifier
- Implement SlackVerifier
- Implement GmailVerifier

### Phase 3: Base Handler (Day 4)
- Create WebhookHandler base class
- Add replay detection
- Add idempotency handling

### Phase 4: Controller Updates (Day 5)
- Update all webhook controllers
- Require credentials
- Test all providers

### Phase 5: Testing (Day 6-7)
- Write verification tests
- Write replay tests
- Write provider tests
- Integration tests

## Files to Create/Modify

```
app/Domains/Shared/Webhooks/
  └── WebhookVerification.php (NEW)
  └── WebhookRequest.php (NEW)
  └── WebhookHandler.php (NEW)
  └── ReplayPrevention.php (NEW)
  └── Verifiers/
      ├── TwilioVerifier.php (NEW)
      ├── ElevenLabsVerifier.php (NEW)
      ├── MetaVerifier.php (NEW)
      ├── SlackVerifier.php (NEW)
      └── GmailVerifier.php (NEW)

extensions/PhoneCallAgent/Http/Controllers/
  └── Webhook/TwilioWebhookController.php (MODIFY)

extensions/AIAgentGmail/Http/Controllers/
  └── GmailWebhookController.php (MODIFY)

extensions/AIAgentSlackChannel/Http/Controllers/
  └── SlackWebhookController.php (MODIFY)

extensions/AIAgentWhatsappChannel/Http/Controllers/
  └── WhatsappWebhookController.php (MODIFY)

tests/Feature/Webhooks/
  └── TwilioVerifierTest.php (NEW)
  └── ElevenLabsVerifierTest.php (NEW)
  └── MetaVerifierTest.php (NEW)
  └── SlackVerifierTest.php (NEW)
  └── ReplayPreventionTest.php (NEW)
  └── WebhookHandlerTest.php (NEW)
```

## Acceptance Criteria Checklist

- [ ] WebhookVerification interface implemented
- [ ] All provider verifiers created
- [ ] Replay prevention working
- [ ] Timestamp validation enforced
- [ ] Credentials required
- [ ] All webhook controllers updated
- [ ] Verification tests passing
- [ ] Replay tests passing
- [ ] Provider tests passing
- [ ] Integration tests passing
- [ ] No regression in existing functionality
- [ ] Documentation updated

## Related Issues

- #143: TenantContext & Authorization Policies
- #145: Credential Vault References
- #20: PhoneCallAgent webhook hardening
- #144: EventEnvelope & Idempotency

## Next Steps

1. Create WebhookVerification interface
2. Implement provider-specific verifiers
3. Create WebhookHandler base class
4. Update all webhook controllers
5. Write comprehensive tests
6. Merge to main
