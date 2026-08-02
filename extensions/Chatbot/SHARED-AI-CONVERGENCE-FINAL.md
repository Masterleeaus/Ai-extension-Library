# Titan Zero Shared AI Convergence

AIChatPro and the Chatbot PWA are separate interfaces backed by one Titan AI runtime.

## Unified execution

Both surfaces resolve `TitanAIRuntimeContract`, which runs:

1. Shared assistant deployment resolution.
2. Shared memory and knowledge context assembly.
3. The five-tier business-action orchestrator.
4. The shared general-conversation engine when no business action matches.
5. Shared capability, provider, model, usage, citation and generative-UI metadata.

The runtime no longer requests a legacy fallback. A missing shared provider returns an explicit safe failure instead of silently switching AI stacks.

## Deployment convergence

Both surfaces can provide `assistant_deployment` in the request payload. The Chatbot can also resolve its existing chatbot record. A deployment controls assistant identity, system prompt, provider, model and capabilities while each surface retains its own UI.

## Capabilities

The same capability catalogue is used by both interfaces. Channel-specific permission narrowing remains supported, so internal features can be hidden from public/customer surfaces without creating another AI implementation.

## Memory and knowledge

Both interfaces send conversation history, memory and knowledge context through the same providers. Persistent storage remains owned by the host application and existing extensions; the shared runtime provides the common read contract.

## Provider configuration

Configure one provider through either a host binding named `titan.ai.general_conversation`, a compatible host completion service, or these environment values:

```env
TITAN_AI_PROVIDER=openai
TITAN_AI_MODEL=gpt-4o-mini
TITAN_AI_ENDPOINT=https://api.openai.com/v1/chat/completions
TITAN_AI_KEY=
```

A BYO provider adapter can bind `titan.ai.general_conversation` and receive the same `TitanAIRequest` from both interfaces.
