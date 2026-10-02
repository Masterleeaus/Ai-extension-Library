# Issue #60: Build capability-composed Voice Engine and migrate PhoneCallAgent (Phase 3)

## Overview
Implement unified Voice Engine with capability-based architecture supporting multiple voice providers, call management, voice command processing, and audio handling.

## Problem Statement
- Voice capabilities scattered across extensions
- No unified call management
- Vendor lock-in risk (single provider)
- Difficult to support new voice providers
- No standardized audio handling

## Requirements

### Voice Engine Architecture
1. **Capability-Based Design**
   - Core capabilities: call setup, audio handling, transcription, TTS, recording
   - Providers implement capabilities
   - Capability negotiation (what can this provider do?)
   - Fallback capabilities

2. **Provider Abstraction**
   - Provider interface contract
   - ElevenLabs adapter (TTS, voice quality)
   - Twilio adapter (call setup, routing)
   - Google Cloud adapter (transcription)
   - Fallback providers (graceful degradation)

3. **Call Management**
   - Unified call lifecycle
   - Call state machine
   - Call routing
   - Call queuing
   - Call transfer/conference

### Voice Features
1. **Voice Command Processing**
   - Transcribe audio to text
   - Recognize commands
   - Handle confidence scoring
   - Fallback to human agent

2. **Text-to-Speech**
   - Generate voice responses
   - Provider selection (quality/speed)
   - Voice customization
   - Audio format support (MP3, WAV, OGG)

3. **Call Recording**
   - Automatic recording
   - Secure storage
   - Playback capability
   - Cleanup policy (TTL)

4. **Transcription**
   - Real-time or post-call
   - Multi-language support
   - Accuracy metrics
   - Searchable index

### PhoneCallAgent Integration
- Migrate PhoneCallAgent to Voice Engine
- Update webhook handlers
- Verify call state consistency
- Performance equivalent or better

## Testing Requirements
- Unit tests for provider adapters
- Integration tests for call flow
- Tests for capability negotiation
- Tests for provider failover
- Tests for concurrent calls
- Performance tests (voice latency)
- Security tests (call recording encryption)

## Acceptance Criteria
- ✅ Voice Engine operational
- ✅ Multiple providers supported
- ✅ PhoneCallAgent migrated
- ✅ Call management unified
- ✅ Voice quality maintained
- ✅ All tests pass (30+ assertions)
- ✅ Performance: <200ms voice command processing

## Related Issues
- Depends on: #143-146, #20, #67, #68, #70, #71
- Blocks: #21 (ChatbotVoice migration)
- Related: #21 (ChatbotVoice migration)

## Timeline
- **Phase 3 Voice Platform**
- Start after: #71
- Estimated effort: 5 days
