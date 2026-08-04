# Issue #149: Add ChatbotVoice & ChatbotVoiceCall Test Suite (Phase 0 Testing)

## Overview
Create comprehensive conformance test suite for ChatbotVoice and ChatbotVoiceCall components, covering voice conversation logic, voice call interactions, and voice command handling.

## Requirements

### Test Categories

1. **Voice Conversation Tests** (9 tests)
   - Parse voice input (transcription)
   - Generate voice response (TTS)
   - Maintain conversation context
   - Handle conversation state
   - Clear conversation history
   - Conversation timeout
   - Multi-turn conversations
   - Interruption handling
   - Voice quality degradation

2. **Voice Call Interaction Tests** (8 tests)
   - Initiate voice call
   - Transfer to human agent
   - Conference call with multiple participants
   - Call recording and playback
   - Call transfer between bots
   - Missed call handling
   - Call priority/queue management
   - Call metrics collection

3. **Voice Command Recognition Tests** (7 tests)
   - Recognize voice commands correctly
   - Handle accent variations
   - Handle background noise
   - Handle speech rate variations
   - Confidence scoring
   - Fallback for low confidence
   - Command disambiguation

4. **Integration Tests** (6 tests)
   - Integration with ChatbotVoice platform
   - Integration with phone system
   - Integration with workflow engine
   - Integration with knowledge base
   - Integration with connector runtime
   - Tenant isolation in voice conversations

5. **Error & Edge Case Tests** (7 tests)
   - Transcription failures handled
   - TTS generation failures handled
   - Audio encoding issues
   - Network connectivity issues
   - Timeout during voice processing
   - Graceful degradation to text
   - Recovery from call drops

## Test Coverage Goals
- 80%+ line coverage
- All voice processing paths tested
- All call state transitions tested
- All integration points verified

## Files to Create
- `tests/Feature/ChatbotVoice/VoiceConversationTest.php` (9 tests)
- `tests/Feature/ChatbotVoice/VoiceCallInteractionTest.php` (8 tests)
- `tests/Feature/ChatbotVoice/VoiceCommandRecognitionTest.php` (7 tests)
- `tests/Feature/ChatbotVoice/VoiceIntegrationTest.php` (6 tests)
- `tests/Feature/ChatbotVoice/VoiceErrorHandlingTest.php` (7 tests)

## Timeline
- **Phase 0 Testing**
- Start after: #143, #144, #145, #146
- Estimated effort: 3 days
