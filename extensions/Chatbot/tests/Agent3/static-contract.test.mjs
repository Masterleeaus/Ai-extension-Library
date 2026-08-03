import fs from 'node:fs';
import assert from 'node:assert/strict';

const source = fs.readFileSync(new URL('../../resources/pwa/chatbot-pwa/security-integrity.js', import.meta.url), 'utf8');
const runtime = fs.readFileSync(new URL('../../resources/pwa/chatbot-pwa/runtime.js', import.meta.url), 'utf8');
const blade = fs.readFileSync(new URL('../../resources/views/frontend-ui/components/pwa.blade.php', import.meta.url), 'utf8');

for (const token of [
  'PBKDF2','AES-GCM','310000','conflicts','permissions','migration_checkpoint','safeReset',
  'prepareSafeReset','integrityCheck','repairIntegrity','applyRevocation','unlock_security',
  'quarantine','data-conflict-field','mergeAppendOnly','verifyAuditChain','audit_chain_head',
  'BroadcastChannel','navigator.locks','SHARED_STATUSES','validateSharedRecord',
  'validateOperationEnvelope','assertPermissionContext','permission-context-mismatch',
  'verifyVaultIntegrity','secureMetadataSet','offline.login','sync.attempted','device.registered'
]) assert.ok(source.includes(token), `missing ${token}`);

assert.match(source, /const DB_VERSION = 8;/, 'security schema must be version 8');
assert.match(runtime, /indexedDB\.open\(DB, 8\)/, 'runtime schema must match version 8');
assert.ok(!source.includes('localStorage.setItem'), 'secrets must not be stored in localStorage');
assert.ok(source.includes('lockout_until'), 'unlock lockout must persist');
assert.ok(source.includes("db.transaction([STORES.vault, STORES.meta], 'readwrite')"), 'vault rotation must commit atomically');
assert.ok(blade.includes('security-integrity.js'), 'security runtime must be loaded by the PWA view');
assert.ok(blade.includes('data-chatbot-conflict-centre'), 'conflict centre host must be present');
console.log('Agent 3 pass-5 static contract tests passed');
