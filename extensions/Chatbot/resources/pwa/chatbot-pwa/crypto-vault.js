(() => {
  'use strict';

  const encoder = new TextEncoder();
  const decoder = new TextDecoder();
  const SALT_KEY = 'vault:salt';
  const CHECK_KEY = 'vault:check';
  let sessionKey = null;

  const bytesToBase64 = bytes => btoa(String.fromCharCode(...new Uint8Array(bytes)));
  const base64ToBytes = value => Uint8Array.from(atob(value), char => char.charCodeAt(0));

  async function deriveKey(passphrase, salt) {
    const material = await crypto.subtle.importKey('raw', encoder.encode(passphrase), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt, iterations: 250000, hash: 'SHA-256' },
      material,
      { name: 'AES-GCM', length: 256 },
      false,
      ['encrypt', 'decrypt']
    );
  }

  async function getOrCreateSalt() {
    const existing = await window.TitanDeviceDB.get('metadata', SALT_KEY);
    if (existing?.value) return base64ToBytes(existing.value);
    const salt = crypto.getRandomValues(new Uint8Array(16));
    await window.TitanDeviceDB.put('metadata', { key: SALT_KEY, value: bytesToBase64(salt), createdAt: Date.now() });
    return salt;
  }

  async function encryptWithKey(key, value) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const data = encoder.encode(JSON.stringify(value));
    const cipher = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, data);
    return { v: 1, alg: 'A256GCM', iv: bytesToBase64(iv), data: bytesToBase64(cipher) };
  }

  async function decryptWithKey(key, envelope) {
    const plain = await crypto.subtle.decrypt(
      { name: 'AES-GCM', iv: base64ToBytes(envelope.iv) },
      key,
      base64ToBytes(envelope.data)
    );
    return JSON.parse(decoder.decode(plain));
  }

  async function initialise(passphrase) {
    if (!passphrase || passphrase.length < 8) throw new Error('The device vault passphrase must contain at least 8 characters.');
    const salt = await getOrCreateSalt();
    const key = await deriveKey(passphrase, salt);
    const check = await window.TitanDeviceDB.get('metadata', CHECK_KEY);
    if (check?.value) {
      try { await decryptWithKey(key, check.value); } catch (_) { throw new Error('Incorrect device vault passphrase.'); }
    } else {
      const value = await encryptWithKey(key, { valid: true, createdAt: Date.now() });
      await window.TitanDeviceDB.put('metadata', { key: CHECK_KEY, value, createdAt: Date.now() });
    }
    sessionKey = key;
    window.dispatchEvent(new CustomEvent('chatbot:vault-unlocked'));
    return true;
  }

  function lock() {
    sessionKey = null;
    window.dispatchEvent(new CustomEvent('chatbot:vault-locked'));
  }

  async function encrypt(value) {
    if (!sessionKey) throw new Error('Device vault is locked.');
    return encryptWithKey(sessionKey, value);
  }

  async function decrypt(envelope) {
    if (!sessionKey) throw new Error('Device vault is locked.');
    return decryptWithKey(sessionKey, envelope);
  }

  async function reset() {
    lock();
    await Promise.all(window.TitanDeviceDB.stores.map(store => window.TitanDeviceDB.clear(store)));
  }

  window.TitanDeviceVault = {
    initialise,
    unlock: initialise,
    lock,
    reset,
    encrypt,
    decrypt,
    isUnlocked: () => Boolean(sessionKey),
    isSupported: () => Boolean(window.crypto?.subtle && window.indexedDB)
  };
})();
