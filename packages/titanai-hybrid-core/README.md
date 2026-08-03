# TitanAI Hybrid Core

Private Laravel Composer package shared by Chatbot, AIChatPro, and AIAgent.

## Responsibilities

- unified registry and contracts;
- failure-isolated event delivery;
- shared contextual memory;
- diagnostics and cross-extension orchestration;
- scheduled expired-memory cleanup.

It does not own conversations, workflows, connectors, or WorkCore operational records.

## Monorepo installation

Register `packages/titanai-hybrid-core` as a Composer path repository in the WorkCore root and require `titanai/hybrid-core:^1.0`.
