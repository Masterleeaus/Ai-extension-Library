# Host Integration Gaps

## Observed supplied Meetup state

The supplied Meetup Laravel application provides authentication, chat, API routes, queues, notifications, and core framework infrastructure. It does not yet expose the authoritative Titan contracts required by this extension:

- company and membership domain
- active-company context
- granular permission authorizer
- extension registry
- Titan capability registry
- Titan Vault secret service
- unified operational audit service
- private signed-export storage adapter
- WorkCore candidate lookup adapter
- WorkCore candidate promotion adapter

## Consequence

The extension is complete as an integration package but must remain disabled in that host until these bindings exist. Enabling it without them intentionally fails closed.

## Required host work

1. Implement company, membership, and active-company context.
2. Register `titan.company-context` and `titan.permission` middleware.
3. Implement Titan Vault references and encrypted secret storage.
4. Implement a capability registrar compatible with manifest capability IDs.
5. Implement audit recording with company, user, agent, conversation, correlation, before, and after context.
6. Implement a private export store that creates short-lived company-authorised links.
7. Implement WorkCore lookup and promotion adapters.
8. Add cross-company isolation tests in the integrated host.
9. Run the extension's Laravel feature tests inside the host.

## Prohibited shortcut

Do not add extension-local users, companies, roles, memberships, plaintext secrets, or duplicate WorkCore tables merely to make the module boot. Those would create competing authorities and invalidate tenant isolation.
