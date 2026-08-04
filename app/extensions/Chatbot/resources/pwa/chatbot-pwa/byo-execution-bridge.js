(() => {
  const settings = () => window.TitanSettings?.get?.() || {};
  const vault = () => window.TitanWorkCore?.vault;
  const jsonHeaders = extra => ({ 'Content-Type': 'application/json', Accept: 'application/json', ...extra });

  function connectedProvider(preferred) {
    const providers = settings().providers || {};
    if (preferred && providers[preferred]?.connected) return [preferred, providers[preferred]];
    return Object.entries(providers).find(([, value]) => value?.connected) || [];
  }

  async function credentials(providerId, record) {
    const deviceVault = vault();
    if (!deviceVault?.isUnlocked?.()) throw new Error('Unlock the device vault before using your BYO AI provider.');
    if (!record?.encrypted) throw new Error(`No encrypted credentials are stored for ${providerId}.`);
    return deviceVault.decrypt(record.encrypted);
  }


  function redactText(value) {
    if (settings().redact_personal === false) return String(value || '');
    return String(value || '')
      .replace(/\b(?:\+?61|0)4\d(?:[\s-]?\d){7}\b/g, '[phone redacted]')
      .replace(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/gi, '[email redacted]')
      .replace(/\b(?:\d[ -]*?){13,19}\b/g, '[payment number redacted]');
  }

  function conversationMessages(message, messages = []) {
    const normalised = Array.isArray(messages) ? messages.filter(item => item && typeof item.content === 'string').map(item => ({ role: item.role === 'assistant' ? 'assistant' : item.role === 'system' ? 'system' : 'user', content: redactText(item.content) })) : [];
    const redactedMessage = redactText(message);
    if (!normalised.length || normalised[normalised.length - 1]?.content !== redactedMessage) normalised.push({ role: 'user', content: redactedMessage });
    return normalised;
  }

  async function requestOpenAI(providerId, secret, input) {
    const base = (secret.base_url || (providerId === 'openrouter' ? 'https://openrouter.ai/api/v1' : providerId === 'ollama' ? 'http://localhost:11434/v1' : 'https://api.openai.com/v1')).replace(/\/$/, '');
    const response = await fetch(`${base}/chat/completions`, {
      method: 'POST',
      headers: jsonHeaders({ Authorization: `Bearer ${secret.api_key}`, ...(providerId === 'openrouter' ? { 'HTTP-Referer': location.origin, 'X-Title': 'Titan Zero' } : {}) }),
      body: JSON.stringify({ model: secret.model || input.model || 'gpt-4o-mini', messages: conversationMessages(input.message, input.messages), temperature: input.temperature ?? 0.2 }),
      signal: input.signal,
    });
    if (!response.ok) throw new Error(`${providerId} request failed (${response.status}).`);
    const payload = await response.json();
    return { message: payload.choices?.[0]?.message?.content || '', provider: providerId, model: payload.model || secret.model || null, usage: payload.usage || null, raw: payload };
  }

  async function requestAnthropic(secret, input) {
    const base = (secret.base_url || 'https://api.anthropic.com/v1').replace(/\/$/, '');
    const history = conversationMessages(input.message, input.messages);
    const system = history.filter(item => item.role === 'system').map(item => item.content).join('\n');
    const response = await fetch(`${base}/messages`, {
      method: 'POST',
      headers: jsonHeaders({ 'x-api-key': secret.api_key, 'anthropic-version': '2023-06-01', 'anthropic-dangerous-direct-browser-access': 'true' }),
      body: JSON.stringify({ model: secret.model || input.model || 'claude-3-5-haiku-latest', max_tokens: input.max_tokens || 2048, ...(system ? { system } : {}), messages: history.filter(item => item.role !== 'system') }),
      signal: input.signal,
    });
    if (!response.ok) throw new Error(`Anthropic request failed (${response.status}).`);
    const payload = await response.json();
    return { message: (payload.content || []).filter(item => item.type === 'text').map(item => item.text).join('\n'), provider: 'anthropic', model: payload.model || secret.model || null, usage: payload.usage || null, raw: payload };
  }

  async function requestGemini(secret, input) {
    const model = secret.model || input.model || 'gemini-2.0-flash';
    const base = (secret.base_url || 'https://generativelanguage.googleapis.com/v1beta').replace(/\/$/, '');
    const response = await fetch(`${base}/models/${encodeURIComponent(model)}:generateContent?key=${encodeURIComponent(secret.api_key)}`, {
      method: 'POST', headers: jsonHeaders(), signal: input.signal,
      body: JSON.stringify({ contents: conversationMessages(input.message, input.messages).filter(item => item.role !== 'system').map(item => ({ role: item.role === 'assistant' ? 'model' : 'user', parts: [{ text: item.content }] })) }),
    });
    if (!response.ok) throw new Error(`Gemini request failed (${response.status}).`);
    const payload = await response.json();
    return { message: payload.candidates?.[0]?.content?.parts?.map(part => part.text || '').join('') || '', provider: 'gemini', model, usage: payload.usageMetadata || null, raw: payload };
  }

  async function execute(input) {
    if (!input?.message) throw new Error('A message is required.');
    const [providerId, record] = connectedProvider(input.provider);
    if (!providerId) throw new Error('No device-held BYO provider is connected.');
    const secret = await credentials(providerId, record);
    let result;
    if (providerId === 'anthropic') result = await requestAnthropic(secret, input);
    else if (providerId === 'gemini') result = await requestGemini(secret, input);
    else result = await requestOpenAI(providerId, secret, input);
    if (!result.message) throw new Error(`${providerId} returned an empty response.`);
    window.dispatchEvent(new CustomEvent('titan:byo-executed', { detail: { provider: providerId, model: result.model, usage: result.usage || null } }));
    return result;
  }

  function isLocalProvider(providerId) {
    return providerId === 'ollama' || providerId === 'localai';
  }

  function shouldUseDeviceProvider() {
    const mode = settings().processing_mode || 'device-first';
    const providerId = connectedProvider()[0];
    if (!providerId) return false;
    if (mode === 'offline-only') return isLocalProvider(providerId);
    return ['device-first', 'private-hybrid', 'cloud-preferred'].includes(mode);
  }

  async function executeIfConfigured(input) {
    if (!shouldUseDeviceProvider()) return null;
    const providerId = input?.provider || connectedProvider()[0];
    if (!navigator.onLine && !isLocalProvider(providerId)) {
      if ((settings().processing_mode || '') === 'offline-only') throw new Error('No on-device language model is configured.');
      return null;
    }
    return execute(input);
  }

  window.TitanBYOExecution = { execute, executeIfConfigured, shouldUseDeviceProvider, connectedProvider: () => connectedProvider()[0] || null };
})();
