# Issue #61: Build Shared Connector Runtime and Migrate Gmail, Slack and WhatsApp Adapters

**Status:** HIGH | Phase 5  
**Effort:** 3-4 weeks  
**Depends on:** #143-146, #20, #25, #31, #58, #64

## Problem: Duplicate Connector Concerns Across Extensions

Currently Gmail, Slack, WhatsApp, Telegram, and Meta extensions each implement their own:
- OAuth credential handling
- Webhook registration and verification
- Inbound message normalization
- Outbound dispatch with retries
- Rate limit handling
- Health monitoring
- Dead letter queues

## Solution: Canonical Connector Runtime

Define ONE authoritative system for all connector operations, with provider-specific adapters.

### Connector Runtime Responsibilities

```php
class ConnectorRuntime {
    /**
     * 1. Connector Installation & Lifecycle
     */
    public function installConnector(
        string $tenantId,
        string $providerName,  // "gmail", "slack", "whatsapp"
        array $config
    ): InstalledConnector {
        // Validate provider
        $provider = $this->providerRegistry->get($providerName);
        
        // Store credential reference in vault
        $credentialRef = $this->vault->store(
            $tenantId,
            "$providerName-credentials",
            $config['oauth_token'] ?? $config['api_key']
        );
        
        // Register webhook
        $webhookUrl = $this->registerWebhook($provider, $credentialRef);
        
        // Create connector instance
        return InstalledConnector::create([
            'tenant_id' => $tenantId,
            'provider' => $providerName,
            'webhook_url' => $webhookUrl,
            'credential_reference' => $credentialRef,
            'status' => 'ACTIVE',
        ]);
    }
    
    /**
     * 2. Verified Inbound Normalization
     */
    public function handleInboundEvent(Request $request): InboundMessage {
        // Verify request signature (from #146)
        $connector = $this->findConnectorByWebhookUrl($request->path);
        $verifier = $this->providerRegistry->getVerifier($connector->provider);
        
        if (!$verifier->verify(WebhookRequest::fromRequest($request))) {
            throw new InvalidWebhookSignature();
        }
        
        // Normalize to canonical format
        $normalizer = $this->providerRegistry->getNormalizer($connector->provider);
        $message = $normalizer->normalize($request->all());
        
        // Ensure tenant isolation
        $message->tenant_id = $connector->tenant_id;
        
        // Record for idempotency
        $this->idempotencyLedger->registerEvent(
            $connector->tenant_id,
            $message->externalId,
            $message
        );
        
        return $message;
    }
    
    /**
     * 3. Observable Outbound Dispatch
     */
    public function sendMessage(
        InstalledConnector $connector,
        OutboundMessage $message
    ): DeliveryReceipt {
        // Get credential from vault
        $credential = $this->vault->retrieve($connector->credential_reference);
        
        // Normalize parameters for provider
        $formatted = $this->providerRegistry->getFormatter($connector->provider)
            ->format($message);
        
        // Send with retry
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < 3) {
            try {
                $externalId = $this->dispatch($connector->provider, $credential, $formatted);
                
                // Record success
                return DeliveryReceipt::success(
                    external_id: $externalId,
                    sent_at: now(),
                    provider: $connector->provider
                );
            } catch (TransientError $e) {
                $lastError = $e;
                $attempt++;
                sleep(2 ** $attempt);  // Exponential backoff
            }
        }
        
        // Failed after retries - queue as dead letter
        $this->deadLetterQueue->enqueue($message, $lastError);
        
        return DeliveryReceipt::failed(error: $lastError);
    }
    
    /**
     * 4. Rate Limit & Health Monitoring
     */
    public function monitorConnectorHealth(
        InstalledConnector $connector
    ): ConnectorHealth {
        // Check rate limit status
        $rateLimitStatus = $this->getRateLimitStatus($connector);
        
        // Check recent delivery success rate
        $successRate = $this->getRecentSuccessRate($connector, minutes: 5);
        
        // Determine overall health
        $health = match(true) {
            $rateLimitStatus->isExceeded() => ConnectorHealth::THROTTLED,
            $successRate < 0.95 => ConnectorHealth::DEGRADED,
            default => ConnectorHealth::HEALTHY,
        };
        
        return new ConnectorHealth(
            connector_id: $connector->id,
            status: $health,
            rate_limit: $rateLimitStatus,
            success_rate: $successRate,
            last_event_at: $this->getLastEventTime($connector)
        );
    }
    
    /**
     * 5. Consent & Customer Identity
     */
    public function enforceConsent(
        InstalledConnector $connector,
        string $customerId
    ): void {
        $consent = $this->consentService->getConsent(
            $connector->tenant_id,
            $customerId,
            $connector->provider
        );
        
        if (!$consent->isActive()) {
            throw new ConsentNotGrantedException(
                "Customer has not consented to communication via {$connector->provider}"
            );
        }
        
        // Check quiet hours
        if ($this->quietHoursService->isInQuietHours($customerId)) {
            throw new QuietHoursException("Outside communication hours");
        }
    }
}
```

### Connector Capability Manifests

```php
class ConnectorCapabilityManifest {
    public string $provider;
    public array $capabilities;  // [MESSAGE, REACTIONS, THREADS, MEDIA, etc]
    public array $rateLimits;    // Requests/min, daily limit
    public array $messageTypes;  // TEXT, IMAGE, VIDEO, FILE, RICH_CARD
    public bool $supportsThreads;
    public bool $supportsReactions;
    public bool $supportsEdits;
    public bool $supportsDeletion;
    public int $maxMessageLength;
    public array $supportedMediaTypes;
}
```

### Provider Adapters (Conformance Pattern)

```php
// Each provider implements this contract
interface ConnectorAdapter {
    public function getManifest(): ConnectorCapabilityManifest;
    public function authorize(array $config): void;
    public function registerWebhook(string $url): string;
    public function normalizeInbound(array $payload): InboundMessage;
    public function formatOutbound(OutboundMessage $message): array;
    public function getRateLimitStatus(): RateLimitStatus;
    public function handleDeliveryStatus(array $event): void;
}

// Gmail adapter
class GmailConnectorAdapter implements ConnectorAdapter {
    public function normalizeInbound(array $payload): InboundMessage {
        return InboundMessage::create([
            'external_id' => $payload['historyId'],
            'provider' => 'gmail',
            'sender' => $payload['payload']['headers']['From'],
            'content' => $payload['payload']['parts'][0]['data'],
            'received_at' => $payload['internalDate'],
            'message_type' => 'EMAIL',
        ]);
    }
}

// Slack adapter  
class SlackConnectorAdapter implements ConnectorAdapter {
    public function normalizeInbound(array $payload): InboundMessage {
        return InboundMessage::create([
            'external_id' => $payload['event']['ts'],
            'provider' => 'slack',
            'sender' => $payload['event']['user'],
            'channel' => $payload['event']['channel'],
            'content' => $payload['event']['text'],
            'received_at' => $payload['event']['ts'],
            'message_type' => 'CHANNEL_MESSAGE',
        ]);
    }
}

// WhatsApp adapter
class WhatsappConnectorAdapter implements ConnectorAdapter {
    public function normalizeInbound(array $payload): InboundMessage {
        $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
        
        return InboundMessage::create([
            'external_id' => $message['id'],
            'provider' => 'whatsapp',
            'sender' => $message['from'],
            'content' => $message['text']['body'] ?? null,
            'media' => $this->normalizeMedia($message['media'] ?? null),
            'received_at' => $message['timestamp'],
            'message_type' => $this->getMessageType($message),
        ]);
    }
}
```

### Dead Letter Queue & Retry

```php
class DeadLetterQueue {
    public function enqueue(OutboundMessage $message, \Throwable $error): void {
        DeadLetter::create([
            'provider' => $message->connector->provider,
            'external_id' => $message->externalId,
            'payload' => $message->toJson(),
            'error_type' => $error::class,
            'error_message' => $error->getMessage(),
            'attempts' => $message->retryCount,
            'created_at' => now(),
        ]);
    }
    
    public function retryAll(): void {
        $deadLetters = DeadLetter::where('last_retry_at', '<', now()->subHours(1))
            ->where('attempts', '<', 5)
            ->get();
        
        foreach ($deadLetters as $letter) {
            try {
                $message = OutboundMessage::fromJson($letter->payload);
                $this->connectorRuntime->sendMessage($message);
                $letter->delete();
            } catch (\Exception $e) {
                $letter->increment('attempts');
                $letter->update(['last_retry_at' => now()]);
            }
        }
    }
}
```

## Exit Criteria

- ✅ Connector Runtime canonical system built
- ✅ Gmail adapter conforms and passes tests
- ✅ Slack adapter conforms and passes tests
- ✅ WhatsApp adapter conforms and passes tests
- ✅ Webhook verification working (from #146)
- ✅ Credential references used (from #145)
- ✅ Rate limiting and health monitoring working
- ✅ Dead letter queue operational
- ✅ Consent enforcement active
- ✅ All existing functionality preserved
- ✅ Cross-connector integration tests passing

