# Phase 3: Voice Platform - Issues #60, #21

## Issue #60: Build Capability-Composed Voice Engine and Migrate PhoneCallAgent

**Status:** HIGH | Phase 3  
**Effort:** 3-4 weeks  
**Depends on:** #143-146, #20, #67-71

### Architecture: Capability-Based Abstraction

Instead of modeling providers (Twilio, ElevenLabs, OpenAI) as interchangeable full-stack systems:

```php
// Define provider CAPABILITIES, not providers
enum CallCapability {
    TELEPHONY,           // Can make/receive phone calls
    SPEECH_RECOGNITION,  // Can transcribe voice
    TEXT_TO_SPEECH,      // Can synthesize speech
    REALTIME_TRANSPORT,  // Streaming/latency-sensitive
    RECORDING,           // Can record calls
    CALL_CONTROL,        // Can hold/transfer
}

// Each provider implements specific capabilities
class ProviderCapabilityMatrix {
    // Twilio = TELEPHONY + RECORDING + CALL_CONTROL
    // ElevenLabs = SPEECH_RECOGNITION + TEXT_TO_SPEECH
    // OpenAI = REALTIME_TRANSPORT + TEXT_TO_SPEECH + SPEECH_RECOGNITION
    // Combine them to build complete solution
}
```

### Canonical Domain Model

```php
class Call {
    public string $callId;              // Immutable unique ID
    public string $tenantId;
    public string $externalCallId;      // Twilio call SID mapping
    public CallStatus $status;
    public ?CallParticipant $initiator;
    public ?CallParticipant $recipient;
    public CallStartedAt $startedAt;
    public ?CallEndedAt $endedAt;
    public CallDuration $duration;
    public ?CallRecording $recording;
    public array $transcript;           // Transcript segments
    public array $providerAttempts;     // Which providers tried
}

class CallTranscriptSegment {
    public int $sequenceNumber;
    public string $speaker;             // "initiator", "recipient", "system"
    public string $text;
    public float $confidence;           // 0.0-1.0
    public string $startTime;
    public string $endTime;
    public array $sentiment;            // Extracted sentiment
}

class CallRecording {
    public string $recordingId;
    public string $storagePath;
    public string $mimeType;
    public int $durationSeconds;
    public RecordingConsent $consent;   // Was consent given?
    public ?DateTime $retentionUntil;   // When to delete
    public string $redactionStatus;     // NONE, IN_PROGRESS, COMPLETE
}
```

### Provider Fallback Strategy

```php
class CapabilityBasedProviderFallback {
    public function synthesizeSpeech(text: string): AudioBuffer {
        // Try providers in order of priority
        $providers = [
            'ElevenLabs' => TTS_PREFERENCE_SCORE,
            'OpenAI' => TTS_FALLBACK_SCORE,
            'GoogleCloud' => TTS_LAST_RESORT,
        ];
        
        foreach ($providers as $provider => $score) {
            try {
                return $provider->synthesize($text);
            } catch (ProviderError $e) {
                $this->recordProviderFailure($provider, $e);
                continue;  // Try next
            }
        }
        
        throw new NoProvidersAvailableException("All TTS providers failed");
    }
}
```

### Usage Accounting and Cost Tracking

```php
class CallUsageMetrics {
    public string $callId;
    public array $providers;            // Which were used
    public int $transcriptionMinutes;
    public int $synthesisCharacters;
    public int $recordingSeconds;
    public decimal $estimatedCostUsd;
    
    public function recordProviderUsage(
        string $provider,
        string $capability,
        int $quantity,
        decimal $unitCostUsd
    ): void {
        $cost = $quantity * $unitCostUsd;
        $this->estimatedCostUsd += $cost;
        
        // Alert if exceeding budget
        if ($this->estimatedCostUsd > $this->budgetUsd) {
            $this->triggerBudgetAlert();
        }
    }
}
```

### Governed Tool Execution During Calls

```php
// #49 and #63 dependencies
class GovtmedCallToolExecution {
    public function executeToolDuringCall(
        Call $call,
        string $toolName,
        array $parameters
    ): array {
        // 1. Verify caller identity
        // 2. Check tool authorization
        // 3. Request approval if needed
        // 4. Execute through governance layer
        // 5. Include result in call transcript
        // 6. Record for audit
        
        return $this->governanceLayer->executeWithApproval(
            tool: $toolName,
            parameters: $parameters,
            context: new ToolExecutionContext(
                call_id: $call->callId,
                caller: $call->initiator,
                actor: $this->tenantContext->getActor()
            )
        );
    }
}
```

## Issue #21: Migrate ChatbotVoice and ChatbotVoiceCall to Shared Voice Engine

**Status:** HIGH | Phase 3  
**Effort:** 2-3 weeks  
**Depends on:** #60

### Migration Strategy

**Phase 1: Dual-Write (Old and New)**
- ChatbotVoice creates records in both old and new systems
- Shadow reads verify new system matches old
- No cutover yet

**Phase 2: Read from New**
- ChatbotVoice reads from new Voice Engine
- Old system continues to receive writes
- Compare results for consistency

**Phase 3: Full Migration**
- All reads from new system
- All writes to new system
- Old system archived

### Backward Compatibility

```php
class ChatbotVoiceCompatibilityAdapter {
    /**
     * Map new Voice Engine calls back to ChatbotVoice schema
     */
    public function getAsLegacyCall(Call $modernCall): ChatbotVoiceCall {
        return ChatbotVoiceCall::create([
            'id' => $modernCall->callId,
            'status' => $this->mapStatus($modernCall->status),
            'conversation_id' => $modernCall->conversationId,
            'transcript' => $this->formatTranscript($modernCall->transcript),
            'recording_url' => $modernCall->recording?->storagePath,
        ]);
    }
    
    /**
     * Map legacy ChatbotVoice records to new Voice Engine
     */
    public function promoteToModernCall(ChatbotVoiceCall $legacy): Call {
        return Call::create([
            'callId' => $legacy->id,
            'tenantId' => $legacy->tenant_id,
            'transcript' => $this->parseTranscript($legacy->transcript),
            'recording' => CallRecording::fromUrl($legacy->recording_url),
        ]);
    }
}
```

### Testing

- Voice conversation flow works
- Audio session management preserved
- TTS/STT integration verified
- Multi-vertical scenarios tested
- Session lifecycle maintained
- Realtime transport working
- Recording consent enforced

