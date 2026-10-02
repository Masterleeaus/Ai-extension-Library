# Issue #21: Migrate ChatbotVoice and ChatbotVoiceCall to shared Voice Engine (Phase 3)

## Overview
Implement migration of ChatbotVoice and ChatbotVoiceCall components to unified Voice Engine platform, consolidating voice capabilities across all AI extensions.

## Requirements

### Voice Engine Integration
1. **Unified Voice Interface**
   - Common VoiceCallRequest/VoiceCallResponse
   - Provider abstraction (ElevenLabs, Twilio, etc.)
   - Standardized voice configuration
   - Tenant isolation

2. **Capability Mapping**
   - ChatbotVoice → Voice Engine voice conversations
   - ChatbotVoiceCall → Voice Engine call management
   - Feature parity maintenance
   - Backward compatibility

### Migration Strategy
1. **Compatibility Layer**
   - Adapter for legacy ChatbotVoice API
   - Feature parity tests
   - Performance equivalence

2. **Gradual Rollout**
   - Use feature flags from #71
   - Shadow reads during transition
   - Monitor error rates
   - Rollback capability

### Voice Engine Requirements
- See Issue #60 (Build capability-composed Voice Engine)
- All voice capabilities abstracted
- Provider adapters available
- Performance: sub-100ms voice command processing

## Acceptance Criteria
- ✅ ChatbotVoice migrated to Voice Engine
- ✅ ChatbotVoiceCall migrated to Voice Engine
- ✅ Feature parity maintained
- ✅ Performance equivalent or better
- ✅ All existing conversations work
- ✅ All tests pass

## Related Issues
- Depends on: #143-146, #20, #67, #68, #70, #71, #60
- Works with: #60 (Voice Engine implementation)
- Blocks: None

## Timeline
- **Phase 3 Voice Platform**
- Start after: #60, #71
- Estimated effort: 3 days
