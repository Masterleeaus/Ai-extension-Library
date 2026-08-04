export class AgentEventBus {
  #listeners = new Map();
  on(type, handler) { const set = this.#listeners.get(type) || new Set(); set.add(handler); this.#listeners.set(type, set); return () => set.delete(handler); }
  async emit(type, payload = {}) { for (const handler of this.#listeners.get(type) || []) await handler(payload); }
}
