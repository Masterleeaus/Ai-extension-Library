# GitHub Issues - ChatbotVoice

Issues to address for ChatbotVoice extension.

## Issue #149: [URGENT] [Phase 0] Add ChatbotVoice & ChatbotVoiceCall Test Suite (15–20 each)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/149
**State**: OPEN
**Labels**: voice, phase-0, critical-path, testing

## Summary
Add comprehensive test coverage for ChatbotVoice and ChatbotVoiceCall extensions.

## Problem
- **ChatbotVoice has ZERO test coverage** — webhook security and provider lifecycle untested
- **ChatbotVoiceCall has ZERO test coverage** — OpenAI realtime integration untested
- 3 overlapping voice implementations (PhoneCallAgent, ChatbotVoice, ChatbotVoiceCall) — consolidation needed later
- Webhook security not verified
- Provider lifecycle (connection, disconnection, failover) untested
- Voice input/output isolation missing
- Transcript deduplication missing

## Solution
Build separate conformance test suites for ChatbotVoice and ChatbotVoiceCall before Phase 3 consolidation.

## ChatbotVoice Tests (15–20 tests)

### Deliverables
- [ ] **Webhook Security Tests (3–4 tests)**
  - [ ] Valid webhook acceptance
  - [ ] Invalid signature rejection
  - [ ] Replay prevention
  - [ ] Tenant resolution

- [ ] **Provider Lifecycle Tests (4–5 tests)**
  - [ ] Provider connection/initialization
  - [ ] Provider health checks
  - [ ] Failover to fallback provider
  - [ ] Provider disconnection
  - [ ] Error recovery

- [ ] **Voice I/O Tests (3–4 tests)**
  - [ ] Audio input capture
  - [ ] Audio output delivery
  - [ ] Input/output isolation (no crosstalk)
  - [ ] Audio format handling

- [ ] **Transcript Tests (2–3 tests)**
  - [ ] Transcript deduplication
  - [ ] Transcript ordering
  - [ ] Partial transcript handling

- [ ] **Knowledge Integration Tests (2–3 tests)**
  - [ ] Knowledge source lookup
  - [ ] Citation accuracy
  - [ ] Multi-source retrieval

### Exit Criteria
- ✅ 15–20 tests pass
- ✅ Webhook security verified
- ✅ Provider lifecycle validated
- ✅ Voice I/O isolated
- ✅ Transcripts deduplicated

---

## ChatbotVoiceCall Tests (15–20 tests)

### Deliverables
- [ ] **Webhook Security Tests (3–4 tests)**
  - [ ] Valid webhook acceptance
  - [ ] Replay prevention
  - [ ] Tenant resolution
  - [ ] Rate limiting

- [ ] **OpenAI Realtime Integration Tests (4–5 tests)**
  - [ ] WebRTC connection establishment
  - [ ] Audio stream handling
  - [ ] Realtime model inference
  - [ ] Connection failures and recovery
  - [ ] Concurrent session handling

- [ ] **Voice I/O Tests (3–4 tests)**
  - [ ] Audio input capture
  - [ ] Audio output delivery
  - [ ] Latency measurements
  - [ ] Quality-of-service monitoring

- [ ] **Transcript & Conversation Tests (3–4 tests)**
  - [ ] Realtime transcript generation
  - [ ] Transcript deduplication
  - [ ] Conversation state persistence
  - [ ] Session recovery

- [ ] **Integration Tests (2–3 tests)**
  - [ ] Chatbot conversation continuation
  - [ ] Message history persistence
  - [ ] User context preservation

### Exit Criteria
- ✅ 15–20 tests pass
- ✅ Webhook security verified
- ✅ OpenAI realtime connection stable
- ✅ Transcripts deduplicated
- ✅ Session recovery works

---

## Relates to
- #60 (Voice Engine consolidation in Phase 3)
- #64 (Smoke and contract tests)
- #137 (AiCaptions transcription)

## Effort
1–2 weeks (combined)

---

