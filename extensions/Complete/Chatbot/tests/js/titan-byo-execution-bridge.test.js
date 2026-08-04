const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const source = fs.readFileSync(__dirname + '/../../resources/pwa/chatbot-pwa/byo-execution-bridge.js', 'utf8');

function makeSandbox({ provider = 'openai', processingMode = 'device-first', online = true } = {}) {
  let captured = null;
  const providerRecord = { connected: true, encrypted: { ciphertext: 'x' } };
  const settings = { processing_mode: processingMode, redact_personal: true, providers: { [provider]: providerRecord } };
  const sandbox = {
    window: {
      TitanWorkCore: { vault: { isUnlocked: () => true, decrypt: async () => ({ api_key: 'secret', model: 'gpt-test', base_url: provider === 'ollama' ? 'http://localhost:11434/v1' : '' }) } },
      TitanSettings: { get: () => settings },
      dispatchEvent() {}
    },
    navigator: { onLine: online },
    location: { origin: 'https://titan.test' },
    fetch: async (url, options) => {
      captured = { url, options };
      return { ok: true, json: async () => ({ choices: [{ message: { content: 'hello' } }] }) };
    },
    CustomEvent: class { constructor(type, init) { this.type = type; this.detail = init?.detail; } },
    structuredClone: global.structuredClone,
    TextDecoder,
    TextEncoder,
    URL,
    console,
  };
  vm.createContext(sandbox);
  vm.runInContext(source, sandbox);
  return { sandbox, getCaptured: () => captured };
}

(async () => {
  {
    const { sandbox, getCaptured } = makeSandbox();
    assert.ok(sandbox.window.TitanBYOExecution, 'bridge must be exported');
    const result = await sandbox.window.TitanBYOExecution.execute({ message: 'Call me on 0412 345 678', provider: 'openai' });
    assert.strictEqual(result.message, 'hello');
    const captured = getCaptured();
    assert.ok(!captured.options.body.includes('0412 345 678'), 'personal phone number must be redacted before cloud execution');
    assert.strictEqual(captured.url, 'https://api.openai.com/v1/chat/completions');
    assert.strictEqual(captured.options.headers.Authorization, 'Bearer secret');
    assert.ok(!JSON.stringify(sandbox.window.TitanSettings.get()).includes('secret'), 'plaintext key must not be persisted');
  }

  {
    const { sandbox, getCaptured } = makeSandbox();
    await sandbox.window.TitanBYOExecution.execute({
      message: 'Call me on 0412 345 678',
      messages: [{ role: 'user', content: 'Call me on 0412 345 678' }],
      provider: 'openai'
    });
    const body = JSON.parse(getCaptured().options.body);
    assert.strictEqual(body.messages.length, 1, 'redaction must not duplicate the current user message');
  }

  {
    const { sandbox, getCaptured } = makeSandbox({ provider: 'ollama', processingMode: 'offline-only', online: false });
    const result = await sandbox.window.TitanBYOExecution.executeIfConfigured({ message: 'Summarise this locally', provider: 'ollama' });
    assert.strictEqual(result.message, 'hello', 'local Ollama execution must remain available when navigator reports offline');
    assert.strictEqual(getCaptured().url, 'http://localhost:11434/v1/chat/completions');
  }

  console.log('BYO execution bridge contract passed');
})().catch(error => { console.error(error); process.exit(1); });
