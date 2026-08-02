import { AgentEventBus } from './event-bus.js';

export class TitanDeviceAgentRuntime {
  constructor({ db, outbox, vault, connectivity } = {}) {
    this.db = db; this.outbox = outbox; this.vault = vault; this.connectivity = connectivity;
    this.events = new AgentEventBus(); this.agents = new Map(); this.started = false;
  }
  register(agent) {
    if (!agent?.id || typeof agent.handle !== 'function') throw new TypeError('Agent requires id and handle(event, context).');
    this.agents.set(agent.id, agent); return this;
  }
  async dispatch(event) {
    const context = { db: this.db, outbox: this.outbox, vault: this.vault, connectivity: this.connectivity, events: this.events };
    const results = [];
    for (const agent of this.agents.values()) {
      if (agent.events?.length && !agent.events.includes(event.type)) continue;
      if (agent.requiresOnline && !this.connectivity?.isReachable?.()) continue;
      results.push({ agent: agent.id, result: await agent.handle(event, context) });
    }
    return results;
  }
  start() { this.started = true; this.events.emit('runtime:started', { at: Date.now() }); }
  stop() { this.started = false; this.events.emit('runtime:stopped', { at: Date.now() }); }
}
