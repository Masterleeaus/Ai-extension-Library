(() => {
  const root = window.TitanWorkCore = window.TitanWorkCore || {};
  const DB = () => root.db;
  const enc = new TextEncoder(), dec = new TextDecoder();
  let masterKey = null, lockTimer = null;
  const iterations = 210000;
  const b64 = bytes => btoa(String.fromCharCode(...new Uint8Array(bytes)));
  const unb64 = value => Uint8Array.from(atob(value), c => c.charCodeAt(0));
  async function derive(passphrase, salt) {
    const material = await crypto.subtle.importKey('raw', enc.encode(passphrase), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey({ name:'PBKDF2', hash:'SHA-256', salt, iterations }, material, { name:'AES-GCM', length:256 }, false, ['encrypt','decrypt']);
  }
  async function create(passphrase) {
    if (!crypto?.subtle) throw new Error('Web Crypto is unavailable.');
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const wrappingKey = await derive(passphrase, salt);
    const rawMaster = crypto.getRandomValues(new Uint8Array(32));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const wrapped = await crypto.subtle.encrypt({ name:'AES-GCM', iv }, wrappingKey, rawMaster);
    await DB().setMeta('vault_envelope', { version:1, iterations, salt:b64(salt), iv:b64(iv), wrapped:b64(wrapped), created_at:new Date().toISOString() });
    masterKey = await crypto.subtle.importKey('raw', rawMaster, 'AES-GCM', false, ['encrypt','decrypt']);
    scheduleLock(); emit('workcore:vault-unlocked'); return true;
  }
  async function unlock(passphrase) {
    const env = await DB().getMeta('vault_envelope'); if (!env) return create(passphrase);
    const wrappingKey = await derive(passphrase, unb64(env.salt));
    const raw = await crypto.subtle.decrypt({ name:'AES-GCM', iv:unb64(env.iv) }, wrappingKey, unb64(env.wrapped));
    masterKey = await crypto.subtle.importKey('raw', raw, 'AES-GCM', false, ['encrypt','decrypt']);
    scheduleLock(); emit('workcore:vault-unlocked'); return true;
  }
  function emit(name, detail={}) { window.dispatchEvent(new CustomEvent(name,{detail})); }
  function lock() { masterKey = null; if (lockTimer) clearTimeout(lockTimer); lockTimer = null; emit('workcore:vault-locked'); }
  function scheduleLock(minutes = 15) { if (lockTimer) clearTimeout(lockTimer); lockTimer = setTimeout(lock, Math.max(1, minutes) * 60000); }
  async function encrypt(value, aad='workcore') {
    if (!masterKey) throw new Error('Device vault is locked.');
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const data = enc.encode(JSON.stringify(value));
    const cipher = await crypto.subtle.encrypt({ name:'AES-GCM', iv, additionalData:enc.encode(aad) }, masterKey, data);
    scheduleLock(); return { version:1, iv:b64(iv), cipher:b64(cipher), aad };
  }
  async function decrypt(payload) {
    if (!masterKey) throw new Error('Device vault is locked.');
    const plain = await crypto.subtle.decrypt({ name:'AES-GCM', iv:unb64(payload.iv), additionalData:enc.encode(payload.aad || 'workcore') }, masterKey, unb64(payload.cipher));
    scheduleLock(); return JSON.parse(dec.decode(plain));
  }
  async function destroy() { lock(); await DB().remove(root.contracts.stores.meta, 'vault_envelope'); emit('workcore:vault-destroyed'); }
  root.vault = { create, unlock, lock, destroy, encrypt, decrypt, isUnlocked:()=>Boolean(masterKey), scheduleLock };
})();
